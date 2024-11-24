
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalacja CMS</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 100%;
            max-width: 500px;
            margin: 50px auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        h1 {
            text-align: center;
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin: 10px 0 5px;
        }
        input[type="text"], input[type="password"], button {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        button {
            background-color: #28a745;
            color: #fff;
            border: none;
            cursor: pointer;
        }
        button:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Instalacja CMS</h1>
    <form method="POST">
        <label for="db_host">Host bazy danych:</label>
        <input type="text" id="db_host" name="db_host" required>

        <label for="db_name">Nazwa bazy danych:</label>
        <input type="text" id="db_name" name="db_name" required>

        <label for="db_user">Użytkownik bazy danych:</label>
        <input type="text" id="db_user" name="db_user" required>

        <label for="db_pass">Hasło bazy danych:</label>
        <input type="password" id="db_pass" name="db_pass" required>

        <label for="admin_username">Nazwa użytkownika administratora:</label>
        <input type="text" id="admin_username" name="admin_username" required>

        <label for="admin_password">Hasło administratora:</label>
        <input type="password" id="admin_password" name="admin_password" required>

        <label for="site_name">Nazwa strony:</label>
        <input type="text" id="site_name" name="site_name" required>

        <button type="submit">Zainstaluj</button>
    </form>
</div>
</body>
</html>
