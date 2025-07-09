<?php
/**
 * senior.php - Página específica Senior Liga 3x3 2025
 * CORREGIDO: Sin warnings de variables indefinidas
 * SISTEMA UNIFICADO: V=2pts, D=1pt (sin empates)
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

// Obtener información de la categoría Senior Masculí
$categoria_id = null;
$categoria_nom = 'SENIOR MASCULÍ';

try {
    $stmt = $pdo->prepare("SELECT ID FROM categoria WHERE Nom = ?");
    $stmt->execute([$categoria_nom]);
    $categoria_data = $stmt->fetch();
    $categoria_id = $categoria_data['ID'];
} catch(PDOException $e) {
    die("Error al obtener categoría: " . $e->getMessage());
}

if (!$categoria_id) {
    die("Categoría Senior Masculí no encontrada");
}

// Obtener todos los partidos de Senior
try {
    $stmt = $pdo->prepare("
        SELECT r.*, c.Nom as CategoriaNom 
        FROM resultats r 
        LEFT JOIN categoria c ON r.Categoria = c.ID 
        WHERE r.Categoria = ?
        ORDER BY r.ID ASC
    ");
    $stmt->execute([$categoria_id]);
    $partidos = $stmt->fetchAll();
} catch(PDOException $e) {
    $partidos = [];
}

// Obtener equipos de Senior (EXCLUYENDO equipos especiales de finales)
try {
    $stmt = $pdo->prepare("
        SELECT e.Nom 
        FROM equips e 
        WHERE e.Categoria = ?
        AND e.Nom NOT IN ('1R INFANTIL MASCULÍ GRUP 1', '1R INFANTIL MASCULÍ GRUP 2', '1R CLASSIFICAT', '2N CLASSIFICAT')
        ORDER BY e.Nom
    ");
    $stmt->execute([$categoria_id]);
    $equipos = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch(PDOException $e) {
    $equipos = [];
}

// ✅ CALCULAR ESTADÍSTICAS PRIMERO (para evitar warnings)
$total_partidos = count($partidos);
$partidos_jugados = 0;
$partidos_programados = 0;

foreach ($partidos as $partido) {
    if ($partido['PuntsLocal'] > 0 || $partido['PuntsVisitant'] > 0) {
        $partidos_jugados++;
    } else {
        $partidos_programados++;
    }
}

// ✅ CLASIFICACIÓN CORREGIDA - Sistema V=2pts, D=1pt (SIN EMPATES)
$clasificacion = [];
foreach ($equipos as $equipo) {
    $stats = [
        'Equipo' => $equipo,
        'Partidos' => 0,
        'Victorias' => 0,
        'Derrotas' => 0,  // ✅ NO hay empates en 3x3
        'PuntosFavor' => 0,
        'PuntosContra' => 0,
        'Diferencia' => 0,
        'Puntos' => 0
    ];
    
    foreach ($partidos as $partido) {
        $es_local = ($partido['EquipLocal'] == $equipo);
        $es_visitante = ($partido['EquipVisitant'] == $equipo);
        
        if ($es_local || $es_visitante) {
            // Solo contar partidos jugados (no 0-0)
            if ($partido['PuntsLocal'] > 0 || $partido['PuntsVisitant'] > 0) {
                $stats['Partidos']++;
                
                if ($es_local) {
                    $stats['PuntosFavor'] += $partido['PuntsLocal'];
                    $stats['PuntosContra'] += $partido['PuntsVisitant'];
                    
                    if ($partido['PuntsLocal'] > $partido['PuntsVisitant']) {
                        $stats['Victorias']++;
                    } else { // PuntsLocal < PuntsVisitant (NO hay empates en 3x3)
                        $stats['Derrotas']++;
                    }
                } else { // es visitante
                    $stats['PuntosFavor'] += $partido['PuntsVisitant'];
                    $stats['PuntosContra'] += $partido['PuntsLocal'];
                    
                    if ($partido['PuntsVisitant'] > $partido['PuntsLocal']) {
                        $stats['Victorias']++;
                    } else { // PuntsVisitant < PuntsLocal
                        $stats['Derrotas']++;
                    }
                }
            }
        }
    }
    
    $stats['Diferencia'] = $stats['PuntosFavor'] - $stats['PuntosContra'];
    
    // ✅ SISTEMA CORREGIDO: Victoria = 2pts, Derrota = 1pt (como el admin)
    $stats['Puntos'] = ($stats['Victorias'] * 2) + ($stats['Derrotas'] * 1);
    
    $clasificacion[] = $stats;
}

// Ordenar igual que el admin: por puntos y diferencia
usort($clasificacion, function($a, $b) {
    if ($b['Puntos'] !== $a['Puntos']) {
        return $b['Puntos'] - $a['Puntos'];
    }
    return $b['Diferencia'] - $a['Diferencia'];
});

// ✅ FILTRAR PARTIDOS REALES (excluyendo finales)
$partidos_reales = [];
foreach ($partidos as $partido) {
    // Excluir partidos con equipos especiales de finales
    if (!in_array($partido['EquipLocal'], ['1R CLASSIFICAT', '2N CLASSIFICAT']) && 
        !in_array($partido['EquipVisitant'], ['1R CLASSIFICAT', '2N CLASSIFICAT'])) {
        $partidos_reales[] = $partido;
    }
}

// Recalcular estadísticas con partidos reales
$total_partidos = count($partidos_reales);
$partidos_jugados = 0;
$partidos_programados = 0;

foreach ($partidos_reales as $partido) {
    if ($partido['PuntsLocal'] > 0 || $partido['PuntsVisitant'] > 0) {
        $partidos_jugados++;
    } else {
        $partidos_programados++;
    }
}
?>
<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Senior Masculí - 3x3 Memorial Joan Hernández Gómez</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/senior.css">
</head>
<body>
    <header>
        <div class="header-content">
            <h1>3x3 - Memorial Joan Hernández Gómez</h1>
            <div class="club-info">
                <img src="../imgs/LogoCBLF.png" alt="CB Les Franqueses Logo" class="club-logo">
                <h1>CB Les Franqueses</h1>
            </div>
        </div>
    </header>

    <nav class="navbar">
        <div class="nav-container">
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="../index.php" class="nav-link">
                        <i class="fas fa-arrow-left"></i> <span>Tornar</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="calendario.php" class="nav-link">
                        <i class="fas fa-calendar-alt"></i> <span>Calendari</span>
                    </a>
                </li>
                <li class="nav-item active">
                    <a href="senior.php" class="nav-link">
                        <i class="fas fa-trophy"></i> <span>Senior Masculí</span>
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
            <h2><i class="fas fa-user-tie"></i> Senior Masculí</h2>
            <p>Resultats, classificació i estadístiques de la categoria</p>
        </div>

        <!-- Estadísticas de la categoría (CORREGIDAS - sin warnings) -->
        <div class="category-stats">
            <div class="stats-grid">
                <div class="stat-item">
                    <span class="stat-number"><?php echo count($equipos); ?></span>
                    <span class="stat-label">Equips</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?php echo $total_partidos; ?></span>
                    <span class="stat-label">Total Partits</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?php echo $partidos_jugados; ?></span>
                    <span class="stat-label">Partits Jugats</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?php echo $partidos_programados; ?></span>
                    <span class="stat-label">Programats</span>
                </div>
            </div>
        </div>

        <!-- Información sobre las finales -->
        <div class="finals-info">
            <h3><i class="fas fa-trophy"></i> Informació Important</h3>
            <p><strong>EN LA CATEGORIA SENIOR MASCULÍ, ELS 2 PRIMERS CLASSIFICATS DISPUTARAN LA FINAL</strong></p>
        </div>

        <!-- Equipos participantes -->
        <div class="section">
            <h3><i class="fas fa-users"></i> Equips Participants</h3>
            <div class="teams-grid">
                <?php foreach ($equipos as $equipo): ?>
                    <div class="team-card">
                        <i class="fas fa-basketball-ball"></i>
                        <span><?php echo htmlspecialchars($equipo); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Partidos (USANDO PARTIDOS REALES, excluyendo finales) -->
        <div class="section">
            <h3><i class="fas fa-list-alt"></i> Partits de Senior Masculí</h3>
            
            <?php if (!empty($partidos_reales)): ?>
                <div class="matches-table">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Equip Local</th>
                                <th>Resultat</th>
                                <th>Equip Visitant</th>
                                <th>Estat</th>
                                <th>Pista</th>
                                <th>Hora</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($partidos_reales as $partido): ?>
                                <?php 
                                $sin_jugar = ($partido['PuntsLocal'] == 0 && $partido['PuntsVisitant'] == 0);
                                $isLocalWinner = $partido['PuntsLocal'] > $partido['PuntsVisitant'];
                                ?>
                                <tr>
                                    <td><strong>#<?php echo $partido['ID']; ?></strong></td>
                                    
                                    <!-- Equipo local -->
                                    <td class="team-cell">
                                        <?php if (!$sin_jugar && $isLocalWinner): ?>
                                            <i class="fas fa-trophy winner-icon"></i>
                                        <?php endif; ?>
                                        <strong><?php echo htmlspecialchars($partido['EquipLocal']); ?></strong>
                                    </td>
                                    
                                    <!-- Resultado -->
                                    <td class="result-cell">
                                        <span class="score <?php echo $sin_jugar ? 'programado' : 'jugado'; ?>">
                                            <?php echo $partido['PuntsLocal'] . ' - ' . $partido['PuntsVisitant']; ?>
                                        </span>
                                    </td>
                                    
                                    <!-- Equipo visitante -->
                                    <td class="team-cell">
                                        <?php if (!$sin_jugar && !$isLocalWinner): ?>
                                            <i class="fas fa-trophy winner-icon"></i>
                                        <?php endif; ?>
                                        <strong><?php echo htmlspecialchars($partido['EquipVisitant']); ?></strong>
                                    </td>
                                    
                                    <!-- Estado -->
                                    <td>
                                        <?php if ($sin_jugar): ?>
                                            <span class="status programado">PROGRAMAT</span>
                                        <?php else: ?>
                                            <span class="status jugado">JUGAT</span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <!-- Pista -->
                                    <td>
                                        <?php if (!empty($partido['Pista'])): ?>
                                            <span class="pista">Pista <?php echo $partido['Pista']; ?></span>
                                        <?php else: ?>
                                            <span class="sin-pista">-</span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <!-- Hora -->
                                    <td>
                                        <?php if (!empty($partido['Hora'])): ?>
                                            <span class="hora"><?php echo date('H:i', strtotime($partido['Hora'])); ?></span>
                                        <?php else: ?>
                                            <span class="sin-hora">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="no-matches">
                    <i class="fas fa-calendar-times"></i>
                    <h4>No hi ha partits registrats</h4>
                    <p>Els partits apareixeran aquí quan estiguin programats.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Clasificación (CORREGIDA - sin warnings) -->
        <div class="section">
            <h3><i class="fas fa-medal"></i> Classificació Senior Masculí</h3>
            
            <?php if (!empty($clasificacion) && $partidos_jugados > 0): ?>
                <div class="classification-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Pos</th>
                                <th>Equip</th>
                                <th>PJ</th>
                                <th>V</th>
                                <!-- ✅ ELIMINAMOS columna de Empates (no existen en 3x3) -->
                                <th>D</th>
                                <th>PF</th>
                                <th>PC</th>
                                <th>Dif</th>
                                <th>Pts</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $position = 1;
                            foreach ($clasificacion as $equipo): 
                                $is_finalist = ($position <= 2); // Los 2 primeros van a final
                            ?>
                                <tr class="<?php echo $is_finalist ? 'finalist-position' : ''; ?>">
                                    <td class="position-cell">
                                        <strong><?php echo $position; ?></strong>
                                        <?php if ($is_finalist): ?>
                                            <i class="fas fa-star finalist-star"></i>
                                        <?php endif; ?>
                                    </td>
                                    <td class="team-name">
                                        <strong><?php echo htmlspecialchars($equipo['Equipo']); ?></strong>
                                        <?php if ($position == 1): ?>
                                            <i class="fas fa-crown champion-crown"></i>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $equipo['Partidos']; ?></td>
                                    <td class="victories"><?php echo $equipo['Victorias']; ?></td>
                                    <!-- ✅ ELIMINAMOS columna de empates -->
                                    <td class="defeats"><?php echo $equipo['Derrotas']; ?></td>
                                    <td><?php echo $equipo['PuntosFavor']; ?></td>
                                    <td><?php echo $equipo['PuntosContra']; ?></td>
                                    <td class="difference"><?php echo ($equipo['Diferencia'] >= 0 ? '+' : '') . $equipo['Diferencia']; ?></td>
                                    <td class="points"><strong><?php echo $equipo['Puntos']; ?></strong></td>
                                </tr>
                            <?php 
                                $position++;
                            endforeach; 
                            ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="classification-legend">
                    <p><i class="fas fa-star" style="color: #8D6E63;"></i> <strong>Els 2 primers classificats disputaran la final</strong></p>
                    <!-- ✅ LEYENDA CORREGIDA: V=2pts, D=1pt -->
                    <p><strong>Sistema de punts 3x3:</strong> Victoria = 2 pts | Derrota = 1 pt (sense empats)</p>
                </div>
                
            <?php else: ?>
                <div class="no-classification">
                    <i class="fas fa-hourglass-half"></i>
                    <h4>Classificació no disponible</h4>
                    <p>La classificació apareixerà quan hi hagi partits jugats.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- NUEVA: Información sobre el partido de final si existe -->
        <?php 
        // Buscar si hay final programada/jugada para esta categoría
        $final_partido = null;
        foreach ($partidos as $partido) {
            if (in_array($partido['EquipLocal'], ['1R CLASSIFICAT', '2N CLASSIFICAT']) || 
                in_array($partido['EquipVisitant'], ['1R CLASSIFICAT', '2N CLASSIFICAT'])) {
                $final_partido = $partido;
                break;
            }
        }
        ?>
        
        <?php if ($final_partido): ?>
            <div class="section" style="background: linear-gradient(135deg, #D7CCC8, #EFEBE9); border: 3px solid #8D6E63;">
                <h3 style="color: #5D4037; text-align: center;"><i class="fas fa-trophy"></i> Final Senior Masculí</h3>
                
                <div style="text-align: center; padding: 20px;">
                    <?php 
                    $final_sin_jugar = ($final_partido['PuntsLocal'] == 0 && $final_partido['PuntsVisitant'] == 0);
                    $final_ganador = null;
                    if (!$final_sin_jugar) {
                        $final_ganador = $final_partido['PuntsLocal'] > $final_partido['PuntsVisitant'] ? 
                                        $final_partido['EquipLocal'] : $final_partido['EquipVisitant'];
                    }
                    ?>
                    
                    <div style="font-size: 1.5em; margin-bottom: 15px; color: #5D4037;">
                        <strong><?php echo htmlspecialchars($final_partido['EquipLocal']); ?></strong>
                        <span style="margin: 0 20px; font-size: 1.2em; color: #8D6E63;">
                            <?php echo $final_partido['PuntsLocal'] . ' - ' . $final_partido['PuntsVisitant']; ?>
                        </span>
                        <strong><?php echo htmlspecialchars($final_partido['EquipVisitant']); ?></strong>
                    </div>
                    
                    <?php if ($final_sin_jugar): ?>
                        <div style="background: #8D6E63; color: white; padding: 10px 20px; border-radius: 25px; display: inline-block; font-weight: bold;">
                            🏆 FINAL PROGRAMADA
                        </div>
                        <?php if ($final_partido['Pista']): ?>
                            <p style="margin-top: 10px; color: #5D4037;">
                                <strong>Pista:</strong> <?php echo $final_partido['Pista']; ?>
                                <?php if ($final_partido['Hora']): ?>
                                    | <strong>Hora:</strong> <?php echo date('H:i', strtotime($final_partido['Hora'])); ?>
                                <?php endif; ?>
                            </p>
                        <?php endif; ?>
                    <?php else: ?>
                        <div style="background: #28a745; color: white; padding: 10px 20px; border-radius: 25px; display: inline-block; font-weight: bold;">
                            🏆 CAMPEÓN: <?php echo htmlspecialchars($final_ganador); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <footer>
        <p>&copy; 3x3 - Memorial Joan Hernández Gómez</p>
    </footer>

    <script>
        // Efectos de hover para las tarjetas de equipos
        document.querySelectorAll('.team-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px) scale(1.05)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });

        // Resaltar fila de clasificación al hover
        document.querySelectorAll('.classification-table tbody tr').forEach(row => {
            row.addEventListener('mouseenter', function() {
                this.style.backgroundColor = 'rgba(141, 110, 99, 0.1)';
            });
            
            row.addEventListener('mouseleave', function() {
                if (!this.classList.contains('finalist-position')) {
                    this.style.backgroundColor = '';
                }
            });
        });

        // Debug info
        if (window.location.hostname === 'localhost') {
            console.log('🏀 Senior Masculí - Liga 3x3 2025');
            console.log('📊 Estadísticas: <?php echo count($equipos); ?> equipos, <?php echo $total_partidos; ?> partidos, <?php echo $partidos_jugados; ?> jugados');
            console.log('✅ Sistema de puntos: Victoria = 2pts, Derrota = 1pt');
        }
    </script>
</body>
</html>