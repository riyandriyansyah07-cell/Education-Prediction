import numpy as np
import pytest

import risk_ml


@pytest.fixture(scope="module")
def trained(data, tmp_path_factory):
    d = tmp_path_factory.mktemp("models")
    train, test = data
    result = risk_ml.train("uji-v1", train, test, models_dir=d)
    return result, d


def test_pelatihan_memenuhi_target_nfr02(trained):
    result, _ = trained
    assert result["algorithm"] in {"random_forest", "xgboost"}
    assert result["metrics"]["macro_f1"] >= 0.70
    assert result["metrics"]["recall_high"] >= 0.80
    assert result["train_rows"] == 280 and result["test_rows"] == 120
    assert set(result["candidates"]) == {"random_forest", "xgboost"}


def test_metrik_dihitung_pada_data_uji(trained, data):
    result, _ = trained
    assert result["metrics"]["n"] == len(data[1])
    total = sum(sum(row.values()) for row in result["metrics"]["confusion"].values())
    assert total == len(data[1])
    assert 0 < result["params"]["sedang"] < result["params"]["tinggi"] <= 8


def test_prediksi_urutan_risiko_masuk_akal(trained):
    _, d = trained
    aman = {
        "attendance": 95, "min_attendance": 90, "low_attendance_courses": 0, "attendance_delta": 2,
        "ips": 3.6, "ipk": 3.55, "ips_delta": 0.2, "failed_ratio": 0, "repeat_courses": 0, "credits_delta": 0,
    }
    bahaya = {
        "attendance": 60, "min_attendance": 50, "low_attendance_courses": 2, "attendance_delta": -10,
        "ips": 1.6, "ipk": 1.8, "ips_delta": -0.6, "failed_ratio": 0.4, "repeat_courses": 2, "credits_delta": 4,
    }
    a, b = risk_ml.predict("uji-v1", [aman, bahaya], models_dir=d)
    assert a["level"] == "rendah" and b["level"] == "tinggi"
    assert a["score"] < b["score"]
    assert 0 <= a["score"] <= 10 and 0 <= b["score"] <= 10
    assert sum(b["probabilities"].values()) == pytest.approx(1, abs=1e-3)


def test_shap_aditif_dan_faktor_terbesar_benar(trained):
    _, d = trained
    art = risk_ml.load("uji-v1", d)
    x = np.array([
        [60, 50, 2, -10, 1.6, 1.8, -0.6, 0.4, 2, 4],
        [95, 90, 0, 2, 3.6, 3.55, 0.2, 0.0, 0, 0],
    ], dtype=float)
    contrib = risk_ml.explain(art, x)
    base = float(np.mean(risk_ml.risk_score(art["model"].predict_proba(art["background"]))))
    scores = risk_ml.risk_score(art["model"].predict_proba(x))
    assert contrib.sum(axis=1) + base == pytest.approx(scores, abs=0.05)
    assert contrib[0].sum() > 0
    assert contrib[1].sum() < 0


def test_versi_tak_valid_atau_tak_ada(trained):
    _, d = trained
    with pytest.raises(FileNotFoundError):
        risk_ml.load("../etc/passwd", d)
    with pytest.raises(FileNotFoundError):
        risk_ml.load("tidak-ada", d)


def test_data_terlalu_sedikit_atau_kelas_hilang(data, tmp_path):
    train, test = data
    with pytest.raises(risk_ml.TrainingError):
        risk_ml.train("kecil", train[:20], test, models_dir=tmp_path)
    tanpa_tinggi = [r for r in train if r["label"] != "tinggi"]
    with pytest.raises(risk_ml.TrainingError, match="tinggi"):
        risk_ml.train("hilang", tanpa_tinggi, test, models_dir=tmp_path)
    with pytest.raises(risk_ml.TrainingError):
        risk_ml.train("../buruk", train, test, models_dir=tmp_path)


def test_hasil_deterministik(data, tmp_path):
    train, test = data
    a = risk_ml.train("a", train, test, models_dir=tmp_path)
    b = risk_ml.train("b", train, test, models_dir=tmp_path)
    assert a["metrics"] == b["metrics"] and a["params"] == b["params"]
