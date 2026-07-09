import pymysql
from datetime import datetime, timedelta
import random

# Database connection
connection = pymysql.connect(
    host='localhost',
    user='root',
    password='',
    database='school_early_warning'
)

cursor = connection.cursor()

# FIX: Use the current year dynamically so registration numbers, enrollment
# dates, attendance records, and assessment records all share the same year.
# Previously the script mixed '2025' (reg numbers, enrollment) with '2026'
# (attendance and assessment academic_year), which made initial_training.py
# unable to find matching records when filtering by academic year.
current_year = datetime.now().year

# Clear existing data to avoid duplicate key errors on re-run
cursor.execute("DELETE FROM assessment_scores")
cursor.execute("DELETE FROM attendance")
cursor.execute("DELETE FROM students")
connection.commit()
print("Cleared existing seed data.")


# Create sample students
classes = ['1', '2', '3', '4']
names = [
    'John Mwakyoma', 'Mary Joseph', 'Peter Mwansasu', 'Grace Kimaro',
    'David Mwakyembe', 'Sarah Mwakipesile', 'James Mwaikambo', 'Elizabeth Mwamwenda',
    'Michael Mwankenja', 'Rachel Mwampagatwa', 'Daniel Mwansakasaka', 'Ruth Mwamfupe',
    'Joseph Mwakyembe', 'Anna Mwansasu', 'Samuel Mwaikambo', 'Rebekah Mwakipesile',
    'Thomas Mwampagatwa', 'Esther Mwamfupe', 'Andrew Mwakyoma', 'Martha Mwankenja'
]

for i, name in enumerate(names):
    class_id = random.choice(classes)
    # FIX: Registration number and enrollment date now use current_year.
    reg_number = f"MBY/{current_year}/{1000 + i}"
    enrollment_date = f"{current_year}-01-15"

    cursor.execute("""
        INSERT INTO students (registration_number, full_name, gender, class_id, enrollment_date, status)
        VALUES (%s, %s, %s, %s, %s, 'Active')
    """, (reg_number, name, random.choice(['M', 'F']), class_id, enrollment_date))

connection.commit()
print(f"Inserted {len(names)} students.")

# Fetch inserted student IDs
cursor.execute("SELECT student_id FROM students WHERE status = 'Active'")
student_ids = [row[0] for row in cursor.fetchall()]

# Generate historical attendance (January to March of current year)
print("Generating attendance data...")
start_date = datetime(current_year, 1, 1)
end_date   = datetime(current_year, 3, 31)
current_date = start_date

attendance_records = []
while current_date <= end_date:
    if current_date.weekday() < 5:  # Monday–Friday only
        for student_id in student_ids:
            # FIX: The original probability thresholds were inverted.
            # attendance_prob < 0.70 → 'Present' (70% chance = correct)
            # attendance_prob < 0.85 → 'Late'    (15% chance)
            # attendance_prob < 0.95 → 'Absent'  (10% chance — but label said Present/Absent)
            # The problem: a realistic school has ~85-90% Present, ~5% Late,
            # ~5-10% Absent. The original logic made 'Late' MORE common than
            # 'Absent', and the comment said "70-95% attendance" which is
            # misleading because Late was not counted as Present in the model.
            # Fixed thresholds give: 85% Present, 7% Late, 6% Absent, 2% Excused.
            p = random.uniform(0, 1)
            if p < 0.85:
                status = 'Present'
            elif p < 0.92:
                status = 'Late'
            elif p < 0.98:
                status = 'Absent'
            else:
                status = 'Excused'

            attendance_records.append((
                student_id,
                current_date.strftime('%Y-%m-%d'),
                status,
                'Term 1',
                str(current_year)   # FIX: was hardcoded '2026'
            ))
    current_date += timedelta(days=1)

cursor.executemany("""
    INSERT INTO attendance (student_id, attendance_date, status, term, academic_year)
    VALUES (%s, %s, %s, %s, %s)
""", attendance_records)
connection.commit()
print(f"Inserted {len(attendance_records)} attendance records.")

# Generate assessment scores
print("Generating assessment data...")
subjects = ['Mathematics', 'English', 'Science', 'History', 'Geography', 'Civics']
assessment_types = ['Test', 'Quiz', 'Assignment', 'Mid-Term', 'Terminal_Exam', 'Annual_Exam']

# FIX: Assessment dates now use current_year.
# Previously they were hardcoded to 2026 dates while the year variable above
# could be different, creating another year mismatch.
assessment_dates = [
    f"{current_year}-01-15", f"{current_year}-01-30",
    f"{current_year}-02-15", f"{current_year}-02-28",
    f"{current_year}-03-15", f"{current_year}-03-30"
]

assessment_records = []
for student_id in student_ids:
    performance_level = random.choice(['high', 'medium', 'low'])

    if performance_level == 'low':
        score_range = (20, 50)
    elif performance_level == 'medium':
        score_range = (45, 70)
    else:
        score_range = (65, 95)

    for date in assessment_dates:
        for subject in random.sample(subjects, 4):
            assessment_type = random.choice(assessment_types)
            total_marks = random.choice([50, 100])
            score = round(random.uniform(score_range[0], score_range[1]), 1)

            # FIX: Cap score to not exceed total_marks.
            # If score_range max (95) is drawn against total_marks=50,
            # score would exceed total, making score_ratio > 1.0 and
            # confusing the risk labeling logic.
            score = min(score, total_marks)

            assessment_records.append((
                student_id, subject, assessment_type,
                score, total_marks,
                'Term 1', str(current_year),  # FIX: was hardcoded '2026'
                date
            ))

cursor.executemany("""
    INSERT INTO assessment_scores
        (student_id, subject, assessment_type, score, total_marks, term, academic_year, assessment_date)
    VALUES (%s, %s, %s, %s, %s, %s, %s, %s)
""", assessment_records)
connection.commit()

print(f"Successfully inserted:")
print(f"   - {len(names)} students")
print(f"   - {len(attendance_records)} attendance records")
print(f"   - {len(assessment_records)} assessment records")
print(f"   - Academic year: {current_year}")

connection.close()
