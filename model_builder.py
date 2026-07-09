import pandas as pd
import numpy as np
from sklearn.ensemble import RandomForestClassifier
from sklearn.model_selection import cross_val_score
from sklearn.preprocessing import StandardScaler
import joblib

class StudentRiskPredictor:
    def __init__(self):
        self.scaler = StandardScaler()
        self.model = RandomForestClassifier(
            n_estimators=100,
            max_depth=10,
            random_state=42
        )
        # FIX: feature_columns must NOT include 'student_id'.
        # Previously, features.columns.tolist() captured every column including
        # 'student_id', so the model was trained with student_id as a feature.
        # This caused two problems:
        #   1. The model leaked identity information into predictions.
        #   2. predict() crashed because the scaler shape didn't match when
        #      student_id order differed between train and predict calls.
        # Solution: store only the actual numeric feature names, excluding 'student_id'.
        self.feature_columns = []

    def engineer_features(self, data):
        """Create features from raw student data"""

        # Attendance features
        attendance_summary = data.groupby('student_id').agg(
            total_days=('status', 'count'),
            absent_days=('status', lambda x: (x == 'Absent').sum())
        ).reset_index()
        attendance_summary['attendance_rate'] = (
            (attendance_summary['total_days'] - attendance_summary['absent_days'])
            / attendance_summary['total_days']
        )

        # Assessment features
        assessment_summary = data.groupby('student_id').agg(
            avg_score=('score', 'mean'),
            std_score=('score', 'std'),
            min_score=('score', 'min'),
            max_score=('score', 'max'),
            avg_total=('total_marks', 'mean')
        ).reset_index()

        # FIX: std_score is NaN when a student has only one assessment.
        # Previously this caused the scaler and model to receive NaN values,
        # silently producing wrong predictions. Fill with 0 (no variance = consistent).
        assessment_summary['std_score'] = assessment_summary['std_score'].fillna(0)

        assessment_summary['score_ratio'] = (
            assessment_summary['avg_score'] / assessment_summary['avg_total']
        )

        # Performance trend (recent decline)
        # FIX: The original lambda used .iloc[2] as the baseline, which is the
        # THIRD record, not the earliest. For a declining trend we want
        # recent_mean - early_mean (negative = declining).
        # Also, .iloc[-1] on a rolling window returns the last rolling average,
        # and .iloc[2] returns the third one — but for students with exactly 3
        # records these happen to be the same row, so the trend was always 0.
        # Correct approach: compare last rolling mean to first rolling mean.
        data_sorted = data.sort_values(['student_id', 'assessment_date'])

        def calc_trend(x):
            if len(x) < 3:
                return 0
            rolling = x['score'].rolling(3, min_periods=2).mean().dropna()
            if len(rolling) < 2:
                return 0
            return float(rolling.iloc[-1] - rolling.iloc[0])  # negative = declining

        recent_trend = (
            data_sorted.groupby('student_id')
            .apply(calc_trend)
            .reset_index(name='recent_trend')
        )

        # Merge all feature sets
        features = attendance_summary.merge(assessment_summary, on='student_id')
        features = features.merge(recent_trend, on='student_id')

        return features

    def prepare_labels(self, data):
        """Create risk labels based on multiple criteria"""
        # Build a per-student summary so labels align with engineer_features output order
        student_ids = data.groupby('student_id').groups.keys()
        labels = []

        for student_id in student_ids:
            student_data = data[data['student_id'] == student_id]
            avg_score = student_data['score'].mean()
            attendance_rate = self._calculate_attendance(student_data)

            if (avg_score < 40 and attendance_rate < 0.7) or attendance_rate < 0.5:
                labels.append(2)  # High risk
            elif avg_score < 50 or attendance_rate < 0.75:
                labels.append(1)  # Medium risk
            else:
                labels.append(0)  # Low risk

        return np.array(labels)

    def train(self, data):
        """Train the model"""
        features = self.engineer_features(data)

        # FIX: Exclude 'student_id' from feature columns used for training.
        # See __init__ comment for full explanation.
        self.feature_columns = [
            col for col in features.columns if col != 'student_id'
        ]

        X = features[self.feature_columns].values
        y = self.prepare_labels(data)

        # FIX: Align label count with feature row count.
        # prepare_labels iterates groupby keys which may differ in order from
        # engineer_features rows. Reindex labels to match features['student_id'].
        label_map = dict(zip(
            list(data.groupby('student_id').groups.keys()),
            y
        ))
        y_aligned = np.array([label_map[sid] for sid in features['student_id']])
        print("Training labels:", np.unique(y_aligned, return_counts=True))

        X_scaled = self.scaler.fit_transform(X)
        self.model.fit(X_scaled, y_aligned)

        cv_scores = cross_val_score(self.model, X_scaled, y_aligned, cv=5)
        print(f"Cross-validation accuracy: {cv_scores.mean():.2f} (+/- {cv_scores.std() * 2:.2f})")

        importance = dict(zip(self.feature_columns, self.model.feature_importances_))
        print("\nFeature Importance:")
        for feat, imp in sorted(importance.items(), key=lambda x: x[1], reverse=True):
            print(f"  {feat}: {imp:.4f}")

        return self

    def predict(self, data):
        """Generate predictions and risk scores"""
        features = self.engineer_features(data)
        X = features[self.feature_columns].values
        X_scaled = self.scaler.transform(X)

        predictions = self.model.predict(X_scaled)
        probabilities = self.model.predict_proba(X_scaled)

        
        # Debug information
        print("Classes:", self.model.classes_)
        print("Probability array shape:", probabilities.shape)


        # Generate risk factors per student
        risk_factors = []
        for _, row in features.iterrows():
            factors = {}
            if row['attendance_rate'] < 0.75:
                factors['low_attendance'] = f"{row['attendance_rate']:.1%}"
            if row['score_ratio'] < 0.5:
                factors['low_scores'] = f"{row['score_ratio']:.1%}"
            if row.get('recent_trend', 0) < -10:
                factors['declining_performance'] = f"{row['recent_trend']:.1f}"
            risk_factors.append(factors)

        # FIX: Return the student_id list from features so api_server.py can
        # iterate it in the same order as predictions/probabilities arrays.
        student_ids = features['student_id'].tolist()

        return predictions, probabilities, risk_factors, student_ids

    def save_model(self, path='student_risk_model.pkl'):
        """Save model and scaler"""
        joblib.dump({
            'model': self.model,
            'scaler': self.scaler,
            'feature_columns': self.feature_columns
        }, path)

    def load_model(self, path='student_risk_model.pkl'):
        """Load saved model"""
        saved = joblib.load(path)
        self.model = saved['model']
        self.scaler = saved['scaler']
        self.feature_columns = saved['feature_columns']

    def _calculate_attendance(self, data):
        if 'status' in data.columns:
            total = len(data)
            absent = (data['status'] == 'Absent').sum()
            return (total - absent) / total if total > 0 else 0
        return 1.0
