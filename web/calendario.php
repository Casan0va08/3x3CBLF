<?php
/**
 * calendario.php - Calendario Liga 3x3 2025
 * Muestra todos los partidos organizados por pista y hora
 * ACTUALIZADO: Usa la categoría directamente de la tabla resultats
 */

// Configuración de la base de datos
$host = "localhost";
$port = "3336";
$db_name = "3x3_2025";
$username = "root";
$password = "";

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$db_name;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch(PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Obtener TODOS los partidos con su categoría directa (ACTUALIZADO)
try {
    $stmt = $pdo->query("
        SELECT r.*, c.Nom as CategoriaNom 
        FROM resultats r 
        LEFT JOIN categoria c ON r.Categoria = c.ID 
        ORDER BY r.Hora ASC, r.Pista ASC
    ");
    $partidos = $stmt->fetchAll();
} catch(PDOException $e) {
    $partidos = [];
}

// Organizar partidos por hora y pista
$calendario = [];
$horas_disponibles = [];
$pistas_disponibles = range(1, 6); // Pistas 1-6

foreach ($partidos as $partido) {
    $hora = $partido['Hora'] ?? '00:00:00';
    $pista = $partido['Pista'] ?? 0;
    
    // Formatear hora para mostrar
    $hora_formateada = $hora ? date('H:i', strtotime($hora)) : 'Sin hora';
    
    if (!in_array($hora_formateada, $horas_disponibles)) {
        $horas_disponibles[] = $hora_formateada;
    }
    
    $calendario[$hora_formateada][$pista] = $partido;
}

// Ordenar horas
sort($horas_disponibles);

// Si no hay horas, agregar algunas por defecto para mostrar la estructura
if (empty($horas_disponibles)) {
    $horas_disponibles = ['09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00'];
}

// Función para determinar el color de la categoría (ACTUALIZADA)
function getCategoryColor($categoria) {
    if (!$categoria) return '#4CAF50'; // Color por defecto
    
    $colores = [
        // Colores según RGB proporcionados
        'PREMINI MIXTE' => '#FFFF00',           // 255, 255, 0 (amarillo)
        'MINI MASCULÍ' => '#FCD5B4',           // 252, 213, 180 (beige/crema)
        'INFANTIL MASCULÍ GRUP 1' => '#C5D9F1', // 197, 217, 241 (azul claro)
        'INFANTIL MASCULÍ GRUP 2' => '#C5D9F1', // 197, 217, 241 (azul claro)
        'INFANTIL FEMENÍ' => '#92D050',         // 146, 208, 80 (verde claro)
        'CADET MASCULÍ' => '#FF0000',           // 255, 0, 0 (rojo)
        'SENIOR MASCULÍ' => '#538ED5',          // 83, 142, 213 (azul)
        'SENIOR FEMENÍ' => '#948B54',           // 148, 139, 84 (verde oliva claro)
        'VETERANS MASCULÍ' => '#4F6228',        // 79, 98, 40 (verde oliva oscuro)
        'FINALS INFANTIL MASCULÍ' => '#C5D9F1', // Mismo que infantil masculí
        
        // Variaciones posibles de nombres
        'PRE-MINI MIXTE' => '#FFFF00',
        'PREMINI' => '#FFFF00',
        'MINI MASCULI' => '#FCD5B4',
        'INFANTIL MASCULI GRUP 1' => '#C5D9F1',
        'INFANTIL MASCULI GRUP 2' => '#C5D9F1',
        'INFANTIL FEMENI' => '#92D050',
        'CADET MASCULI' => '#FF0000',
        'SENIOR MASCULI' => '#538ED5',
        'SENIOR FEMENI' => '#948B54',
        'VETERANS MASCULI' => '#4F6228',
    ];
    
    // Buscar el color, si no existe usar color por defecto
    return $colores[strtoupper($categoria)] ?? '#4CAF50';
}

// Función para determinar si el texto debe ser negro o blanco
function getTextColor($backgroundColor) {
    // Colores que necesitan texto negro (fondos claros)
    $fondos_claros = [
        '#FFFF00',  // Pre-Mini (amarillo)
        '#FCD5B4',  // Mini Masculí (beige/crema)
        '#C5D9F1',  // Infantil Masculí (azul claro)
        '#92D050',  // Infantil Femení (verde claro)
    ];
    
    // Si el color está en la lista de fondos claros, usar texto negro
    if (in_array(strtoupper($backgroundColor), $fondos_claros)) {
        return '#000';
    }
    
    // Para el resto de colores (más oscuros), usar texto blanco
    return '#fff';
}
?>
<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendari - 3x3 Memorial Joan Hernández Gómez</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/calendario.css">
</head>
<body>
    <header>
        <div class="header-content">
            <h1>3x3 - Memorial Joan Hernández Gómez</h1>
            <div class="club-info">
                <img src="../imgs/LogoCBLF.png" alt="CB Les Franqueses Logo" class="club-logo">
                <h1>CB Les Franqueses</h1>
            </div>
                <h3>Calendari de Partits</h3>
        </div>
    </header>
    
    <nav class="navbar">
        <div class="nav-container">
            <ul class="nav-menu">
                <li class="nav-item active">
                    <a href="calendario.php" class="nav-link">
                        <i class="fas fa-calendar-alt"></i> <span>Calendari</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="../index.php" class="nav-link">
                        <i class="fas fa-trophy"></i> <span>Resultats</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="./reglament.php" class="nav-link">
                        <i class="fas fa-book"></i> <span>Reglament</span>
                    </a>
                </li>
            </ul>
        </div>
    </nav>
    
    <div class="container">
        <div class="page-title">
            <h2><i class="fas fa-calendar-alt"></i> Calendari de Partits</h2>
            <p>Tots els partits organitzats per pista i horari</p>
        </div>

        <!-- Estadísticas del calendario -->
        <div class="calendar-stats">
            <div class="stats-grid">
                <div class="stat-item">
                    <span class="stat-number"><?php echo count($partidos); ?></span>
                    <span class="stat-label">Total Partits</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?php echo count($horas_disponibles); ?></span>
                    <span class="stat-label">Franges Horàries</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">6</span>
                    <span class="stat-label">Pistes Disponibles</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">
                        <?php 
                        $partidos_jugados = 0;
                        foreach ($partidos as $p) {
                            if ($p['PuntsLocal'] > 0 || $p['PuntsVisitant'] > 0) {
                                $partidos_jugados++;
                            }
                        }
                        echo $partidos_jugados;
                        ?>
                    </span>
                    <span class="stat-label">Partits Jugats</span>
                </div>
            </div>
        </div>
        
        <!-- Información sobre las finales -->
        <div style="background: rgba(255, 255, 255, 0.95); border-radius: 20px; padding: 25px; margin-bottom: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
            <h3 style="color: #ff6b35; text-align: center; margin-bottom: 20px;">
                <i class="fas fa-trophy"></i> Informació sobre les Finals
            </h3>
            <div style="color: #333; line-height: 1.8; font-size: 1.1em;">
                <div style="margin-bottom: 15px;">
                    <strong style="color: #ff6b35;">•</strong> <strong>EN LES CATEGORIES PRE-MINI I MINI MASCULÍ, HI HAURÀ FINAL ENTRE ELS 2 PRIMERS CLASSIFICATS DE LA LLIGUETA</strong>
                </div>
                <div style="margin-bottom: 15px;">
                    <strong style="color: #ff6b35;">•</strong> <strong>EN LA CATEGORIA INFANTIL MASCULÍ, HI HAURÀ FINAL ENTRE ELS PRIMERS CLASSIFICATS DE CADA GRUP</strong>
                </div>
                <div>
                    <strong style="color: #ff6b35;">•</strong> <strong>PER LA RESTA DE CATEGORIES, L'EQUIP GUANYADOR SORTIRÀ DE LA LLIGUETA</strong>
                </div>
            </div>
        </div>
        
        <!-- Tabla del calendario -->
        <div class="calendar-container">
            <div class="calendar-grid">
                <!-- Header de las pistas -->
                <div class="calendar-header-row">
                    <div class="time-header">Hora</div>
                    <?php foreach ($pistas_disponibles as $pista): ?>
                        <div class="pista-header">
                            <i class="fas fa-basketball-ball"></i>
                            Pista <?php echo $pista; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Filas de horarios -->
                <?php foreach ($horas_disponibles as $hora): ?>
                    <div class="calendar-row">
                        <div class="time-cell">
                            <strong><?php echo $hora; ?></strong>
                        </div>
                        
                        <?php foreach ($pistas_disponibles as $pista): ?>
                            <div class="match-cell">
                                <?php if (isset($calendario[$hora][$pista])): ?>
                                    <?php $partido = $calendario[$hora][$pista]; ?>
                                    <?php 
                                    // ACTUALIZADO: Usar la categoría directamente del partido
                                    $categoria = $partido['CategoriaNom'];
                                    $color_fondo = getCategoryColor($categoria);
                                    $color_texto = getTextColor($color_fondo);
                                    $sin_jugar = ($partido['PuntsLocal'] == 0 && $partido['PuntsVisitant'] == 0);
                                    ?>
                                    
                                    <div class="match-card" style="background-color: <?php echo $color_fondo; ?>; color: <?php echo $color_texto; ?>;">
                                        <div class="match-id">
                                            #<?php echo $partido['ID']; ?>
                                        </div>
                                        
                                        <?php if ($categoria): ?>
                                            <div class="categoria"><?php echo htmlspecialchars($categoria); ?></div>
                                        <?php else: ?>
                                            <div class="categoria" style="color: #dc3545; font-weight: bold;">Sin categoría</div>
                                        <?php endif; ?>
                                        
                                        <div class="match-teams">
                                            <div class="team local">
                                                <strong><?php echo htmlspecialchars($partido['EquipLocal']); ?></strong>
                                                <span class="points"><?php echo $partido['PuntsLocal']; ?></span>
                                            </div>
                                            
                                            <div class="vs">VS</div>
                                            
                                            <div class="team visitant">
                                                <strong><?php echo htmlspecialchars($partido['EquipVisitant']); ?></strong>
                                                <span class="points"><?php echo $partido['PuntsVisitant']; ?></span>
                                            </div>
                                        </div>
                                        
                                        <div class="match-info">
                                            <?php if ($sin_jugar): ?>
                                                <span class="status programado">PROGRAMAT</span>
                                            <?php elseif ($partido['PuntsLocal'] == $partido['PuntsVisitant']): ?>
                                                <span class="status empate">EMPAT</span>
                                            <?php else: ?>
                                                <span class="status jugado">JUGAT</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                <?php else: ?>
                                    <div class="empty-slot">
                                        <i class="fas fa-plus-circle"></i>
                                        <span>Lliure</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if (empty($partidos)): ?>
            <div class="no-matches">
                <i class="fas fa-calendar-times"></i>
                <h3>No hi ha partits programats</h3>
                <p>Els partits apareixeran aquí quan estiguin registrats al sistema.</p>
            </div>
        <?php endif; ?>
    </div>

    <footer>
        <p>&copy; 3x3 - Memorial Joan Hernández Gómez</p>
    </footer>

    <script>
        // Efectos de hover para las tarjetas de partidos
        document.querySelectorAll('.match-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'scale(1.05)';
                this.style.zIndex = '10';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'scale(1)';
                this.style.zIndex = '1';
            });
        });

        // Mostrar información de debug sobre categorías (solo en desarrollo)
        if (window.location.hostname === 'localhost') {
            console.log('🏀 Sistema de categorías independientes activo');
            console.log('📊 Total partidos: <?php echo count($partidos); ?>');
            
            // Contar partidos por categoría
            const partidosPorCategoria = {};
            <?php foreach ($partidos as $p): ?>
                const categoria = '<?php echo addslashes($p['CategoriaNom'] ?? 'Sin categoría'); ?>';
                partidosPorCategoria[categoria] = (partidosPorCategoria[categoria] || 0) + 1;
            <?php endforeach; ?>
            
            console.log('📈 Partidos por categoría:', partidosPorCategoria);
        }
    </script>
</body>
</html>