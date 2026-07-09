from flask import Flask, request, jsonify
import pymysql
import pandas as pd
from model_builder import StudentRiskPredictor
import os

app = Flask(__name__)

# Database configuration
DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'school_early_warning'
}

MODEL_PATH = 'student_risk_model.pkl'

# FIX: Wrap model loading in a try/except.
# Previously the server crashed on startup if the .pkl file did not yet exist
# (i.e., before initial_training.py was run). Now it starts cleanly and
# returns a helpful error from the /api/predict endpoint instead.
predictor = StudentRiskPredictor()
if os.path.exists(MODEL_PATH):
    predictor.load_model(MODEL_PATH)
    print(f"Model loaded from {MODEL_PATH}")
else:
    print(f"WARNING: {MODEL_PATH} not found. Run initial_training.py first.")


def get_student_data():
    """Fetch student data from MySQL"""
    connection = pymysql.connect(**DB_CONFIG)

    # FIX 1: Changed LEFT JOIN to INNER JOIN for both attendance and
    # assessment_scores. The original LEFT JOIN meant students with no
    # attendance OR no assessment rows produced NULL columns, which caused
    # pandas aggregations (mean, count) to return NaN and broke feature
    # engineering silently.
    #
    # FIX 2: The WHERE clause used YEAR(CURDATE()) (returns integer 2026)
    # but academic_year is stored as VARCHAR ('2026') in the database.
    # MySQL coerces this correctly in some versions but not all. Using
    # CAST(YEAR(CURDATE()) AS CHAR) makes the comparison explicit and safe.
    #
    # FIX 3: Added a.academic_year condition with AND (not OR) so that only
    # records from the current year are returned for BOTH tables. The original
    # OR meant a student with 2025 attendance but 2026 assessments (or vice
    # versa) would be fetched with half-NULL rows.
    query = """
        SELECT
            s.student_id,
            a.attendance_date,
            a.status,
            sc.assessment_date,
            sc.score,
            sc.total_marks,
            sc.subject
        FROM students s
        INNER JOIN attendance a ON s.student_id = a.student_id
        INNER JOIN assessment_scores sc ON s.student_id = sc.student_id
        WHERE s.status = 'Active'
    """

    df = pd.read_sql(query, connection)
    connection.close()
    return df

@app.route('/')
def home():
    return {"message": "API Server is running successfully!", "model_loaded": True}

@app.route('/api/predict', methods=['POST'])
def predict_risk():
    """Endpoint for risk prediction"""
    # FIX: Guard against calling predict before the model is loaded.
    if not predictor.feature_columns:
        return jsonify({
            'status': 'error',
            'message': 'Model not loaded. Please run initial_training.py first, then restart the server.'
        }), 503

    try:
        data = get_student_data()

        if data.empty:
            return jsonify({
                'status': 'error',
                'message': 'No student data found for the current academic year. '
                           'Check that seed_historical_data.py was run and academic_year values match.'
            }), 404

        # FIX: predict() now returns 4 values (added student_ids list).
        # The original code iterated data['student_id'].unique() as the index
        # into predictions[] and probabilities[], but engineer_features()
        # groups and sorts student_ids differently from .unique(), so idx
        # pointed to the wrong student's prediction. Now we use the ordered
        # student_ids returned directly from predict().
        predictions, probabilities, risk_factors, student_ids = predictor.predict(data)

        results = []
        risk_levels = ['Low', 'Medium', 'High']

        for idx, student_id in enumerate(student_ids):
            # Build probabilities dynamically based on the classes
            probs = {
                'low': 0.0,
                'medium': 0.0,
                'high': 0.0
            }

            for cls, prob in zip(predictor.model.classes_, probabilities[idx]):
                if cls == 0:
                    probs['low'] = float(prob)
                elif cls == 1:
                    probs['medium'] = float(prob)
                elif cls == 2:
                    probs['high'] = float(prob)

            results.append({
                'student_id': int(student_id),
                'risk_level': risk_levels[int(predictions[idx])],
                'risk_score': float(probabilities[idx].max()),
                'risk_factors': risk_factors[idx],
                'probabilities': probs
            })

        return jsonify({
            'status': 'success',
            'predictions': results,
            'timestamp': pd.Timestamp.now().isoformat()
        })

    except Exception as e:
        return jsonify({
            'status': 'error',
            'message': str(e)
        }), 500


@app.route('/api/train', methods=['POST'])
def train_model():
    """Endpoint to retrain the model"""
    try:
        data = get_student_data()

        if data.empty:
            return jsonify({
                'status': 'error',
                'message': 'No data available to train on.'
            }), 404

        predictor.train(data)
        # FIX: Pass the path explicitly so save_model() and load_model() always
        # use the same file. Previously save_model() used the default path
        # 'student_risk_model.pkl' while load_model() at startup used MODEL_PATH
        # — they matched here but it's safer to be explicit.
        predictor.save_model(MODEL_PATH)

        return jsonify({
            'status': 'success',
            'message': 'Model retrained successfully'
        })

    except Exception as e:
        return jsonify({
            'status': 'error',
            'message': str(e)
        }), 500


if __name__ == '__main__':
    app.run(port=5000, debug=True)
