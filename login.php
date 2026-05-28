<?php
session_start();

// Подключение к БД
$db_user = 'u82669';
$db_pass = '9085380'; 

try {
    $db = new PDO('mysql:host=localhost;dbname=u82669', $db_user, $db_pass,
        [PDO::ATTR_PERSISTENT => true, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    die("Ошибка подключения к БД");
}

// Обработка POST-запроса (отправка формы входа)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login = trim($_POST['login'] ?? '');
    $pass = trim($_POST['pass'] ?? '');
    $error = '';
    
    if (empty($login) || empty($pass)) {
        $error = 'Заполните все поля';
    } else {
        // Ищем пользователя в БД
        $stmt = $db->prepare("SELECT * FROM users_auth WHERE login = ?");
        $stmt->execute([$login]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Проверяем пароль (сравниваем с md5 хешем)
        if ($user && $user['password_hash'] == md5($pass)) {
            $_SESSION['login'] = $user['login'];
            $_SESSION['uid'] = $user['id'];
            header('Location: index.php');
            exit();
        } else {
            $error = 'Неверный логин или пароль';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в систему</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Montserrat', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .login-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 400px;
            padding: 40px;
        }
        
        h1 {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
            font-size: 28px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #555;
        }
        
        input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border 0.3s;
        }
        
        input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        button:hover {
            transform: translateY(-2px);
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 14px;
        }
        
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-link a {
            color: #667eea;
            text-decoration: none;
        }
        
        .back-link a:hover {
            text-decoration: underline;
        }
        
        .info {
            background: #e7f3ff;
            color: #0066cc;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>Вход в систему</h1>
        
        <?php if (isset($error) && $error): ?>
            <div class="error">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if (empty($_SESSION['login'])): ?>
            <div class="info">
                🔐 Введите логин и пароль, которые вы получили после отправки формы
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Логин</label>
                <input type="text" name="login" required placeholder="user_12345">
            </div>
            <div class="form-group">
                <label>Пароль</label>
                <input type="password" name="pass" required placeholder="••••••••">
            </div>
            <button type="submit">Войти</button>
        </form>
        
        <div class="back-link">
            <a href="index.php">← Вернуться к форме</a>
        </div>
    </div>
</body>
</html>