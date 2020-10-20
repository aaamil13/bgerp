<?php

/*****************************************************************************
 *                                                                           *
 *      Примерен конфигурационен файл за приложение в Experta Framework      *
 *                                                                           *
 *      След като се попълнят стойностите на константите, този файл          *
 *      трябва да бъде записан в [conf] директорията под име:                *
 *      [име на приложението].cfg.php                                        *
 *                                                                           *
 *****************************************************************************/




/***************************************************
*                                                  *
* Параметри за връзка с базата данни               *
*                                                  *
****************************************************/

// Име на базата данни. По подразбиране е същото, като името на приложението
DEFINE('EF_DB_NAME', EF_APP_NAME);

// Потребителско име. По подразбиране е същото, като името на приложението
DEFINE('EF_DB_USER', EF_APP_NAME);

// По-долу трябва да се постави реалната парола за връзка
// с базата данни на потребителя, дефиниран в предходния ред
DEFINE('EF_DB_PASS', 'USER_PASSWORD_FOR_DB');

// Адреса на MySQL сървъра
DEFINE('EF_DB_HOST', 'localhost');
 
// Кодировка на базата данни. По подразбиране utf8mb4
// DEFINE('EF_DB_CHARSET', 'utf8mb4');

// Виртуален хост по подразбиране в CMS-a
DEFINE('BGERP_VHOST', 'localhost');

// Абсолютен път до скрипта за клониране на домейн в CMS-a
DEFINE('BGERP_CLONE_VHOST_SCRIPT','');


/**
 * Секретни ключове, използвани за кодиране в рамките на системата
 * Трябва да са различни, за различните инсталации
 * Моля сменете стойността, ако правите нова инсталация.
 * След като веднъж са установени, тези параметри не трябва да се променят
 **/
// Обща сол
DEFINE('EF_SALT', '');

// "Подправка" за кодиране на паролите
DEFINE('EF_USERS_PASS_SALT', '');

// Препоръчителна стойност между 200 и 500
DEFINE('EF_USERS_HASH_FACTOR', 0);

// Git бранч - на основния пакет
DEFINE('BGERP_GIT_BRANCH', 'master');

// Вербално заглавие на приложението
DEFINE('EF_APP_TITLE', 'bgERP');

/***************************************************
*                                                  *
* Някои от другите възможни константи              *
*                                                  *
****************************************************/

// Базова директория, където се намират по-директориите за
// временните файлове. По подразбиране е системната папка за временни файлове
 # DEFINE( 'EF_TEMP_BASE_PATH', 'PATH_TO_FOLDER');

// Директория за качване на потребителски файлове.
// По подразбиране е EF_ROOT_PATH/uploads/EF_APP_NAME
 # DEFINE('EF_UPLOADS_PATH', 'PATH_TO_FOLDER');

// Език на интерфейса по подразбиране. Ако не се дефинира
// се приема, че езика по подрзбиране е български
 # DEFINE('EF_DEFAULT_LANGUAGE', 'bg');

// Дали вместо ник, за име на потребителя да се приема
// неговия имейл адрес. По подразбиране се приема, че
// трябва да се изисква отделен ник, въведен от потребителя
 # DEFINE('EF_USSERS_EMAIL_AS_NICK', TRUE);
 
// Статично задаване на домейна, в който отвън се вижда bgERP
 # DEFINE('BGERP_ABSOLUTE_HTTP_HOST', 'bgerp.mycompany.com');
 
// Git бранч - на частния пакет - ако не е дефинирано се взима бранча на основния пакет
 # DEFINE('PRIVATE_GIT_BRANCH', 'master');

// Път до gs GHOST SCRIPT командата
 # DEFINE('FILEMAN_GHOSTSCRIPT_PATH', '/var/www/ghostscript-9.23-linux-x86_64/gs-923-linux-x86_64');

// Игнориране на затварянето на модул "Help"
 # DEFINE('BGERP_DEMO_MODE', false);


/**
 * URL за отдалечено репортване на грешки
 */
DEFINE('EF_REMOTE_ERROR_REPORT_URL', 'https://experta.bg/ci_Errors/add/apiKey/rmon123/');


/**
 * Отдалечен сървър за генериране на лого на фирма
 */
DEFINE('CRM_REMOTE_COMPANY_LOGO_CREATOR', 'https://experta.bg/api_Companies/getLogo/apiKey/crm123/');
