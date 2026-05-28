<?php
/**
 * api.php - Веб-сервис для работы с формой
 * Поддерживает методы:
 * POST /api.php - создание новой записи (неавторизованный)
 * PUT /api.php/{id} - обновление данных (авторизованный)
 * GET /api.php/{id} - получение данных (авторизованный)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, PUT, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Ответ на preflight запрос (для PUT)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// ============================================
// ПОДКЛЮЧЕНИЕ К БАЗЕ ДАННЫХ
// ============================================
$db_user = 'u82669';
$db_pass = 'ВАШ_ПАРОЛЬ'; // ЗАМЕНИТЕ НА ВАШ РЕАЛЬНЫЙ ПАРОЛЬ

try {
    $db = new PDO('mysql:host=localhost;dbname=u82669', $db_user, $db_pass,
        [PDO::ATTR_PERSISTENT => true, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit();
}

// ============================================
// ПОЛУЧАЕМ ДАННЫЕ ИЗ ЗАПРОСА
// ============================================
$input = json_decode(file_get_contents('php://input'), true);
$method = $_SERVER['REQUEST_METHOD'];
$path = $_SERVER['PATH_INFO'] ?? '';
$action = trim($path, '/');

// ============================================
// POST /api.php - СОЗДАНИЕ НОВОЙ ЗАПИСИ
// ============================================
if ($method == 'POST' && $action == '') {
    $errors = validateData($input, $db);
    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode(['errors' => $errors]);
        exit();
    }
    
    $login = 'user_' . rand(10000, 99999);
    $pass = substr(md5(uniqid()), 0, 8);
    $password_hash = md5($pass);
    
    try {
        $db->beginTransaction();
        
        $stmt = $db->prepare("INSERT INTO users_auth (login, password_hash) VALUES (?, ?)");
        $stmt->execute([$login, $password_hash]);
        $auth_id = $db->lastInsertId();
        
        $stmt = $db->prepare("INSERT INTO users (full_name, phone, email, birth_date, gender, biography, agreed, user_id) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $input['full_name'], $input['phone'], $input['email'], $input['birth_date'],
            $input['gender'], $input['biography'] ?? '', $input['agreed'] ?? 0, $auth_id
        ]);
        $user_id = $db->lastInsertId();
        
        $stmt_lang = $db->prepare("SELECT id FROM programming_languages WHERE name = ?");
        $stmt_insert = $db->prepare("INSERT INTO user_languages (user_id, language_id) VALUES (?, ?)");
        foreach ($input['languages'] as $lang) {
            $stmt_lang->execute([$lang]);
            $lang_id = $stmt_lang->fetchColumn();
            if ($lang_id) {
                $stmt_insert->execute([$user_id, $lang_id]);
            }
        }
        
        $db->commit();
        
        // ВОЗВРАЩАЕМ JSON БЕЗ РЕДИРЕКТА
        echo json_encode([
            'success' => true,
            'login' => $login,
            'password' => $pass,
            'profile_url' => "http://u82669.kubsu-dev.ru/lab8/"
        ]);
        
    } catch (PDOException $e) {
        $db->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
    exit();
}

// ============================================
// PUT /api.php/{id} - ОБНОВЛЕНИЕ ДАННЫХ АВТОРИЗОВАННОГО
// ============================================
if ($method == 'PUT' && is_numeric($action)) {
    session_start();
    if (empty($_SESSION['login'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit();
    }
    
    $errors = validateData($input, $db);
    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode(['errors' => $errors]);
        exit();
    }
    
    try {
        // Получаем ID записи пользователя
        $stmt = $db->prepare("SELECT id FROM users WHERE user_id = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$action]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            $stmt = $db->prepare("UPDATE users SET full_name=?, phone=?, email=?, birth_date=?, gender=?, biography=?, agreed=? 
                                  WHERE id=?");
            $stmt->execute([
                $input['full_name'], $input['phone'], $input['email'], $input['birth_date'],
                $input['gender'], $input['biography'] ?? '', $input['agreed'] ?? 0, $user['id']
            ]);
            
            $stmt_del = $db->prepare("DELETE FROM user_languages WHERE user_id = ?");
            $stmt_del->execute([$user['id']]);
            
            $stmt_lang = $db->prepare("SELECT id FROM programming_languages WHERE name = ?");
            $stmt_insert = $db->prepare("INSERT INTO user_languages (user_id, language_id) VALUES (?, ?)");
            foreach ($input['languages'] as $lang) {
                $stmt_lang->execute([$lang]);
                $lang_id = $stmt_lang->fetchColumn();
                if ($lang_id) {
                    $stmt_insert->execute([$user['id'], $lang_id]);
                }
            }
        }
        
        // ВОЗВРАЩАЕМ JSON БЕЗ РЕДИРЕКТА
        echo json_encode(['success' => true]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
    exit();
}

// ============================================
// GET /api.php/{id} - ПОЛУЧЕНИЕ ДАННЫХ ПОЛЬЗОВАТЕЛЯ
// ============================================
if ($method == 'GET' && is_numeric($action)) {
    session_start();
    if (empty($_SESSION['login'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit();
    }
    
    try {
        $stmt = $db->prepare("SELECT * FROM users WHERE user_id = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$action]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            $stmt_lang = $db->prepare("SELECT pl.name FROM user_languages ul 
                                       JOIN programming_languages pl ON ul.language_id = pl.id 
                                       WHERE ul.user_id = ?");
            $stmt_lang->execute([$user['id']]);
            $langs = $stmt_lang->fetchAll(PDO::FETCH_COLUMN);
            $user['languages'] = $langs;
        }
        
        echo json_encode($user ?: []);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
    exit();
}

// ============================================
// ФУНКЦИЯ ВАЛИДАЦИИ ДАННЫХ
// ============================================
function validateData($data, $db) {
    $errors = [];
    
    // ФИО
    if (empty($data['full_name'])) {
        $errors['full_name'] = 'ФИО обязательно для заполнения.';
    } elseif (strlen($data['full_name']) > 150) {
        $errors['full_name'] = 'ФИО не должно превышать 150 символов.';
    } elseif (!preg_match('/^[a-zA-Zа-яА-ЯёЁ\s\-]+$/u', $data['full_name'])) {
        $errors['full_name'] = 'ФИО должно содержать только буквы, пробелы и дефисы.';
    }
    
    // Телефон
    if (empty($data['phone'])) {
        $errors['phone'] = 'Телефон обязателен для заполнения.';
    } elseif (!preg_match('/^[\d\s\+\(\)-]{5,20}$/', $data['phone'])) {
        $errors['phone'] = 'Телефон должен содержать только цифры, пробелы, +, (, ), - (5-20 символов).';
    }
    
    // Email
    if (empty($data['email'])) {
        $errors['email'] = 'Email обязателен для заполнения.';
    } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Введите корректный email (пример: name@domain.ru).';
    }
    
    // Дата рождения
    if (empty($data['birth_date'])) {
        $errors['birth_date'] = 'Дата рождения обязательна для заполнения.';
    } elseif (strtotime($data['birth_date']) > strtotime(date('Y-m-d'))) {
        $errors['birth_date'] = 'Дата рождения не может быть в будущем.';
    }
    
    // Пол
    if (empty($data['gender']) || !in_array($data['gender'], ['male', 'female'])) {
        $errors['gender'] = 'Выберите пол.';
    }
    
    // Языки
    $allowed_langs = ['Pascal', 'C', 'C++', 'JavaScript', 'PHP', 'Python', 'Java', 'Haskell', 'Clojure', 'Prolog', 'Scala', 'Go'];
    if (empty($data['languages'])) {
        $errors['languages'] = 'Выберите хотя бы один язык программирования.';
    } else {
        foreach ($data['languages'] as $lang) {
            if (!in_array($lang, $allowed_langs)) {
                $errors['languages'] = 'Выбран недопустимый язык программирования.';
                break;
            }
        }
    }
    
    // Согласие
    if (empty($data['agreed'])) {
        $errors['agreed'] = 'Вы должны ознакомиться с контрактом.';
    }
    
    return $errors;
}
?>