import pandas as pd
from model_builder import StudentRiskPredictor
import pymysql
from datetime import datetime

# Connect to database
connection = pymysql.connect(
    host='localhost',
    user='root',
    password='',
    database='school_early_warning'
)

# FIX: The original script hardcoded academic_year = '2025', but
# seed_historical_data.py inserts records with academic_year = '2026'
# (current year). This meant the training query returned 0 rows, so the
# model was trained on an empty DataFrame and saved a broken .pkl file.
# Solution: always use the current year dynamically.
current_year = str(datetime.now().year)

# FIX: Changed LEFT JOIN to INNER JOIN (same reason as api_server.py):
# LEFT JOIN produced NULL-filled rows for students missing attendance or
# assessment records, which crashed feature engineering.
query = f"""
    SELECT s.student_id,
           a.attendance_date, a.status,
           sc.assessment_date, sc.score, sc.total_marks, sc.subject
    FROM students s
    INNER JOIN attendance a ON s.student_id = a.student_id
    INNER JOIN assessment_scores sc ON s.student_id = sc.student_id
    WHERE s.status = 'Active'
      AND a.academic_year = '{current_year}'
      AND sc.academic_year = '{current_year}'
"""

df = pd.read_sql(query, connection)
connection.close()

if df.empty:
    print(f"ERROR: No data found for academic year {current_year}.")
    print("Make sure seed_historical_data.py has been run first.")
    exit(1)

print(f"Loaded {len(df)} rows for {df['student_id'].nunique()} students (year {current_year})")

# Train and save model
predictor = StudentRiskPredictor()
predictor.train(df)
predictor.save_model('student_risk_model.pkl')
print("Initial model trained and saved successfully!")
