<?php
// Configurações gerais do sistema

define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3307');
define('DB_NAME', 'Restaurante');
define('DB_USER', 'root');
define('DB_PASS', '');

define('SESSION_TIMEOUT', 60 * 60); // 60 minutos em segundos

define('TURNO_ALMOCO_INICIO', '11:00:00');
define('TURNO_ALMOCO_FIM', '15:00:00');
define('TURNO_JANTAR_INICIO', '18:00:00');
define('TURNO_JANTAR_FIM', '23:00:00');

define('RESERVA_ANTECEDENCIA_MINIMA_HORAS', 2);
define('RESERVA_CANCELAMENTO_GRATUITO_HORAS', 1);

define('BASE_URL', '/BD_II_RESTAURANTE');
