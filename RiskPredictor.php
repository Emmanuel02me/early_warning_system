<?php
require_once 'config.php';

class RiskPredictor {
    private $db;

    public function __construct() {
        $this->db = getDBConnection();
    }

    public function getPredictions() {
        // Call Python API
        // FIX: The original code sent a POST request with no body. The Flask
        // endpoint get_student_data() fetches data independently, so no body
        // is needed, but Content-Type without a body causes some server
        // configurations to hang. Added CURLOPT_POSTFIELDS as empty JSON.
        $ch = curl_init(ML_API_URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, '{}');
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        // FIX: Added a timeout so dashboard.php does not hang indefinitely if
        // the Python server is down.
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

         // FIX: Add these two lines below to bypass local Windows SSL/Network restrictions
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            error_log("ML API connection error: " . $curlError);
            return false;
        }

        if ($httpCode == 200) {
            $data = json_decode($response, true);
            // FIX: Check that 'predictions' key actually exists before passing
            // it to storePredictions(). The original code assumed the key was
            // always present; if the API returned an error JSON the call to
            // storePredictions(null) would crash with a foreach on null.
            if (isset($data['predictions'])) {
                $this->storePredictions($data['predictions']);
                return $data['predictions'];
            }
            error_log("ML API returned unexpected response: " . $response);
            return false;
        }

        error_log("ML API returned HTTP $httpCode: $response");
        return false;
    }

    private function storePredictions($predictions) {
        // Clear old predictions for today
        $stmt = $this->db->prepare("DELETE FROM risk_flags WHERE DATE(prediction_date) = CURDATE()");
        $stmt->execute();

        // Store new predictions
        $stmt = $this->db->prepare(
            "INSERT INTO risk_flags (student_id, risk_level, risk_score, factors_json, prediction_date)
             VALUES (:student_id, :risk_level, :risk_score, :factors, CURDATE())"
        );

        foreach ($predictions as $pred) {
            $stmt->execute([
                ':student_id' => $pred['student_id'],
                ':risk_level' => $pred['risk_level'],
                ':risk_score' => $pred['risk_score'],
                ':factors'    => json_encode($pred['risk_factors'])
            ]);
        }
    }

    public function getAtRiskStudents($riskLevel = null) {
        // FIX: The original query used rf.* which includes columns like
        // student_id from risk_flags, potentially overwriting s.student_id,
        // s.full_name etc. when both tables share column names. Explicit
        // column selection avoids this ambiguity.
        $query = "SELECT
                      s.student_id,
                      s.registration_number,
                      s.full_name,
                      s.class_id,
                      s.gender,
                      rf.risk_level,
                      rf.risk_score,
                      rf.factors_json,
                      rf.intervention_status,
                      rf.prediction_date
                  FROM risk_flags rf
                  INNER JOIN students s ON rf.student_id = s.student_id
                  WHERE DATE(rf.prediction_date) = CURDATE()";

        if ($riskLevel) {
            $query .= " AND rf.risk_level = :risk_level";
        }

        $query .= " ORDER BY rf.risk_score DESC";

        $stmt = $this->db->prepare($query);

        if ($riskLevel) {
            $stmt->execute([':risk_level' => $riskLevel]);
        } else {
            $stmt->execute();
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRiskSummary() {
        $stmt = $this->db->prepare(
            "SELECT risk_level, COUNT(*) as count
             FROM risk_flags
             WHERE DATE(prediction_date) = CURDATE()
             GROUP BY risk_level"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateIntervention($studentId, $status) {
        $stmt = $this->db->prepare(
            "UPDATE risk_flags
             SET intervention_status = :status
             WHERE student_id = :student_id
             AND DATE(prediction_date) = CURDATE()"
        );
        $stmt->execute([
            ':student_id' => $studentId,
            ':status'     => $status
        ]);
    }
}
?>
