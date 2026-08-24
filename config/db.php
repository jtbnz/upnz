<?php
/**
 * Database configuration and connection
 * Using SQLite for simplicity - no server setup required
 */

class Database {
    private static $instance = null;
    private $db;
    private $dbPath;
    
    private function __construct() {
        // Store database in data directory
        $this->dbPath = dirname(__DIR__) . '/data/images.db';
        
        // Create data directory if it doesn't exist
        $dataDir = dirname($this->dbPath);
        if (!is_dir($dataDir)) {
            mkdir($dataDir, 0755, true);
        }
        
        try {
            // Connect to SQLite database (creates if doesn't exist)
            $this->db = new PDO('sqlite:' . $this->dbPath);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Create tables if they don't exist
            $this->createTables();
            
        } catch (PDOException $e) {
            die('Database connection failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Get database instance (singleton pattern)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get PDO connection
     */
    public function getConnection() {
        return $this->db;
    }
    
    /**
     * Create required tables
     */
    private function createTables() {
        $sql = "CREATE TABLE IF NOT EXISTS image_order (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            filename TEXT UNIQUE NOT NULL,
            display_order INTEGER NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        $this->db->exec($sql);
        
        // Create index on display_order for faster sorting
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_display_order ON image_order(display_order)");
    }
    
    /**
     * Get all images ordered by display_order
     */
    public function getOrderedImages() {
        $stmt = $this->db->prepare("SELECT filename, display_order FROM image_order ORDER BY display_order ASC, id ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Add new image to database
     */
    public function addImage($filename) {
        // Get the highest display_order
        $stmt = $this->db->prepare("SELECT MAX(display_order) as max_order FROM image_order");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $newOrder = ($result['max_order'] ?? 0) + 1;
        
        // Insert new image
        $stmt = $this->db->prepare("INSERT OR IGNORE INTO image_order (filename, display_order) VALUES (:filename, :order)");
        $stmt->execute([
            ':filename' => $filename,
            ':order' => $newOrder
        ]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Remove image from database
     */
    public function removeImage($filename) {
        $stmt = $this->db->prepare("DELETE FROM image_order WHERE filename = :filename");
        $stmt->execute([':filename' => $filename]);
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Update image order
     */
    public function updateOrder($orderData) {
        $this->db->beginTransaction();
        
        try {
            $stmt = $this->db->prepare("UPDATE image_order SET display_order = :order, updated_at = CURRENT_TIMESTAMP WHERE filename = :filename");
            
            foreach ($orderData as $item) {
                $stmt->execute([
                    ':filename' => $item['filename'],
                    ':order' => $item['order']
                ]);
            }
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
    
    /**
     * Sync database with actual files in directory
     */
    public function syncWithDirectory($directory) {
        // Get all files from database
        $dbFiles = [];
        $stmt = $this->db->query("SELECT filename FROM image_order");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $dbFiles[$row['filename']] = true;
        }
        
        // Get all image files from directory
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $dirFiles = [];
        
        if (is_dir($directory)) {
            $files = scandir($directory);
            foreach ($files as $file) {
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if (in_array($ext, $allowedExtensions)) {
                    $dirFiles[$file] = true;
                    
                    // Add to database if not exists
                    if (!isset($dbFiles[$file])) {
                        $this->addImage($file);
                    }
                }
            }
        }
        
        // Remove from database if file doesn't exist
        foreach (array_keys($dbFiles) as $filename) {
            if (!isset($dirFiles[$filename])) {
                $this->removeImage($filename);
            }
        }
    }
}

// Initialize database on first use
Database::getInstance();