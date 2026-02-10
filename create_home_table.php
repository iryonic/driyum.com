<?php
require_once 'config/database.php';

$sql = "CREATE TABLE IF NOT EXISTS homepage_sections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    section_name VARCHAR(50) UNIQUE NOT NULL,
    heading VARCHAR(255),
    subheading TEXT,
    media_url VARCHAR(255),
    video_url VARCHAR(255),
    cta_text VARCHAR(100),
    cta_link VARCHAR(255),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

$conn = get_db_connection();
if ($conn->query($sql)) {
    echo "<h1>Homepage Sections Table Created!</h1>";
    
    // Seed default Video Section data
    $check = $conn->query("SELECT * FROM homepage_sections WHERE section_name = 'video_brand_story'");
    if ($check->num_rows == 0) {
        $stmt = $conn->prepare("INSERT INTO homepage_sections (section_name, heading, subheading, media_url, video_url) VALUES (?, ?, ?, ?, ?)");
        $name = 'video_brand_story';
        $head = "FROM KASHMIR \nWITH LOVE.";
        $sub = "Experience the journey of our premium treats. No machines, just mountain air and traditional processing.";
        $img = "assets/images/hero.jpg";
        $vid = "#";
        $stmt->bind_param("sssss", $name, $head, $sub, $img, $vid);
        $stmt->execute();
        echo "<p>Seeded default Video Section data.</p>";
    }
} else {
    echo "Error: " . $conn->error;
}
?>


