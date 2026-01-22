<?php
require_once 'includes/functies.php';

/**
 * Hulpscript voor aanpassen van database (schema migratie)
 * Gebruik dit script éénmalig om kolommen toe te voegen aan de `posts` tabel
 * (bijvoorbeeld tijdens ontwikkeling of bij upgrades).
 */

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$conn->set_charset(DB_CHARSET);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Controleer of kolom image_url bestaat (voeg toe als ontbreekt)
$result = $conn->query("SHOW COLUMNS FROM posts LIKE 'image_url'");

if ($result->num_rows == 0) {
    // Voeg kolom image_url toe als deze niet bestaat
    $sql = "ALTER TABLE posts ADD COLUMN image_url VARCHAR(255)";
    if ($conn->query($sql) === TRUE) {
        echo "Kolom 'image_url' succesvol toegevoegd<br>";
    } else {
        echo "Fout bij toevoegen kolom: " . $conn->error . "<br>";
    }
} else {
    echo "Kolom 'image_url' bestaat al<br>";
}

// Controleer of kolom posts_id bestaat
$result = $conn->query("SHOW COLUMNS FROM posts LIKE 'posts_id'");

if ($result->num_rows == 0) {
    // Voeg kolom posts_id toe als deze niet bestaat
    $sql = "ALTER TABLE posts ADD COLUMN posts_id INT";
    if ($conn->query($sql) === TRUE) {
        echo "Kolom 'posts_id' succesvol toegevoegd<br>";
    } else {
        echo "Fout bij toevoegen kolom: " . $conn->error . "<br>";
    }
} else {
    echo "Kolom 'posts_id' bestaat al<br>";
}

// Toon huidige tabelstructuur
echo "<br>Huidige posts tabelstructuur:<br>";
$result = $conn->query("DESCRIBE posts");
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "<br>";
}

$conn->close();
?>
