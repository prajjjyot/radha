<?php
class db_functions {
    private $conn;

    public function __construct() {
        $this->conn = new mysqli("localhost", "root", "", "qgen");
        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }
        $this->conn->set_charset("utf8mb4");
    }

    public function get_conn() {
        return $this->conn;
    }

    /* ---------- AUTH (matches your table) ---------- */

    public function insert_user($email, $password) {
        $stmt = $this->conn->prepare("SELECT 1 FROM qgen_registration WHERE email_id = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) { $stmt->close(); return false; }
        $stmt->close();

        // NOTE: Column name is PASSWORD (uppercase) in your table
        $stmt = $this->conn->prepare("INSERT INTO qgen_registration (email_id, PASSWORD) VALUES (?, ?)");
        $stmt->bind_param("ss", $email, $password);
        return $stmt->execute();
    }

    /** Return full user row (id, email_id) on success; null otherwise */
 public function login_user($email, $password) {
    $stmt = $this->conn->prepare("SELECT id, email_id FROM qgen_registration WHERE email_id = ? AND PASSWORD = ?");
    $stmt->bind_param("ss", $email, $password);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    return $row ?: false; // returns user row or false
}


    /* ---------- HISTORY ---------- */

    /**
     * Save generated history
     * $generator: 'basic' | 'advanced'
     * $task_name: e.g., 'insert_data' (null for basic)
     * $columns_text: raw columns input string
     * $query_line: one-line SQL (advanced) or null
     * $full_query: final SQL/functions shown to user
     */
  public function save_history($user_id, $generator, $task_name, $table_name, $columns_text, $query_line, $full_query, $generated_functions) {
    $stmt = $this->conn->prepare("
        INSERT INTO qgen_history 
        (user_id, generator, task_name, table_name, columns_text, query_line, full_query, generated_functions)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param(
        "isssssss",
        $user_id,
        $generator,
        $task_name,
        $table_name,
        $columns_text,
        $query_line,
        $full_query,
        $generated_functions
    );
    return $stmt->execute();
}


    public function get_history_by_user($user_id) {
        $stmt = $this->conn->prepare("
            SELECT id, generator, task_name, table_name, columns_text, query_line, full_query, created_at
            FROM qgen_history
            WHERE user_id = ?
            ORDER BY created_at DESC, id DESC
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        return $stmt->get_result();
    }

    // Get all history for a user
public function get_user_history($user_id) {
    $stmt = $this->conn->prepare("SELECT * FROM qgen_history WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Delete a history entry for a user
public function delete_history($history_id, $user_id) {
    $stmt = $this->conn->prepare("DELETE FROM qgen_history WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $history_id, $user_id);
    return $stmt->execute();
}

public function getConnection() {
    return $this->conn;
}


}
