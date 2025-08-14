<?php require('db.php');
include('auth.php'); 
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard – Salon Management</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/animejs/3.2.1/anime.min.js"></script>
  <style>
    .fade-in {
      animation: fadeIn 0.5s ease-in-out;
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .card-hover {
      transition: all 0.3s ease;
    }
    .card-hover:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
    }
    .sidebar-link {
      transition: all 0.2s ease;
    }
    .sidebar-link:hover {
      transform: translateX(5px);
    }
  </style>
</head>
<body class="bg-gray-50 font-sans">