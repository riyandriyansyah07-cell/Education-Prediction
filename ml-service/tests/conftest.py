import sys
from pathlib import Path

import numpy as np
import pytest

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

PROFILES = {
    "tinggi": (68, 8, 1.9, 0.35, 0.18, 0.50, -0.45, 0.30),
    "sedang": (79, 6, 2.45, 0.30, 0.04, 0.20, -0.12, 0.25),
    "rendah": (91, 5, 3.30, 0.28, 0.00, 0.05, 0.10, 0.20),
}


def synthetic_rows(n: int = 400, seed: int = 2026) -> list[dict]:
    rng = np.random.default_rng(seed)
    rows = []
    for _ in range(n):
        r = rng.integers(1, 101)
        label = "tinggi" if r <= 22 else ("sedang" if r <= 57 else "rendah")
        am, a_s, im, i_s, f0, f1, dm, ds = PROFILES[label]
        att = round(float(np.clip(rng.normal(am, a_s), 30, 100)), 2)
        min_att = round(float(np.clip(att - abs(rng.normal(6, 3)), 20, att)), 2)
        low_att = 2 if att < 72.0 else (1 if min_att < 75.0 else 0)
        att_delta = round(float(np.clip(rng.normal(dm * 12, 5), -35, 25)), 2)
        ips = round(float(np.clip(rng.normal(im, i_s), 0.5, 4)), 2)
        ips_delta = round(float(np.clip(rng.normal(dm, ds), -3, 3)), 2)
        ipk = round(float(np.clip(ips - ips_delta * 0.3, 0.8, 4.0)), 2)
        failed_ratio = round(float(f0 + (f1 - f0) * rng.random()), 4)
        repeat_courses = 2 if failed_ratio >= 0.25 else (1 if failed_ratio >= 0.10 else 0)
        credits_delta = 4 if label == "tinggi" else (2 if label == "sedang" else 0)

        rows.append({
            "attendance": att,
            "min_attendance": min_att,
            "low_attendance_courses": low_att,
            "attendance_delta": att_delta,
            "ips": ips,
            "ipk": ipk,
            "ips_delta": ips_delta,
            "failed_ratio": failed_ratio,
            "repeat_courses": repeat_courses,
            "credits_delta": credits_delta,
            "label": label,
        })
    return rows


def split(rows):
    train = [r for i, r in enumerate(rows) if i % 10 < 7]
    test = [r for i, r in enumerate(rows) if i % 10 >= 7]
    return train, test


@pytest.fixture(scope="session")
def data():
    return split(synthetic_rows())
