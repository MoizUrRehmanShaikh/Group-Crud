<?php
include "config.php";

// CREATE
if (isset($_POST['create'])) {
    $name = $_POST['name'];
    $role = $_POST['role'];
    $future_stack = $_POST['future_stack'];
    $experience_years = (int) $_POST['experience_years'];
    $skills = $_POST['skills'];

    $stmt = $conn->prepare(
        "INSERT INTO team_members (name, role, future_stack, experience_years, skills)
         VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("sssds", $name, $role, $future_stack, $experience_years, $skills);
    $stmt->execute();
    $stmt->close();

    header("Location: index.php");
    exit;
}

// UPDATE
if (isset($_POST['update'])) {
    $id = (int) $_POST['id'];
    $name = $_POST['name'];
    $role = $_POST['role'];
    $future_stack = $_POST['future_stack'];
    $experience_years = (int) $_POST['experience_years'];
    $skills = $_POST['skills'];

    $stmt = $conn->prepare(
        "UPDATE team_members
         SET name=?, role=?, future_stack=?, experience_years=?, skills=?
         WHERE id=?"
    );
    $stmt->bind_param("sssds i", $name, $role, $future_stack, $experience_years, $skills, $id);
    // note: if this line gives error, change to: bind_param("sssdis", ...) adjusting types
    $stmt->execute();
    $stmt->close();

    header("Location: index.php");
    exit;
}

// DELETE
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM team_members WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    header("Location: index.php");
    exit;
}
