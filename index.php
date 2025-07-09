<?php
/**
 * index.php - Página principal pública Liga 3x3 2025
 * Muestra todas las categorías disponibles para ver resultados y clasificaciones
 * ACTUALIZADO: Considera categorías independientes de partidos
 */

// Configuración de la base de datos (adapta estos valores)
$host = "localhost";
$port = "3336"; // Tu puerto personalizado
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

// Obtener todas las categorías con estadísticas SIMPLIFICADAS (EXCLUYENDO FINALES)
try {
    // Primero obtener todas las categorías
    $stmt = $pdo->query("SELECT ID, Nom FROM categoria WHERE Nom NOT LIKE '%FINALS%' ORDER BY ID");
    $all_categories = $stmt->fetchAll();
    
    $categories = [];
    
    foreach ($all_categories as $cat) {
        // Contar equipos por categoría (excluyendo los especificados)
        $equipos_stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM equips 
            WHERE Categoria = ? 
            AND Nom NOT IN ('1R INFANTIL MASCULÍ GRUP 1', '1R INFANTIL MASCULÍ GRUP 2', '1R CLASSIFICAT', '2N CLASSIFICAT')
        ");
        $equipos_stmt->execute([$cat['ID']]);
        $num_equipos = $equipos_stmt->fetchColumn();
        
        // Contar partidos por categoría
        $partidos_stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total,
                COUNT(CASE WHEN PuntsLocal > 0 OR PuntsVisitant > 0 THEN 1 END) as jugados,
                COUNT(CASE WHEN PuntsLocal = 0 AND PuntsVisitant = 0 THEN 1 END) as programados
            FROM resultats 
            WHERE Categoria = ?
        ");
        $partidos_stmt->execute([$cat['ID']]);
        $partidos_data = $partidos_stmt->fetch();
        
        // Solo incluir categorías que tienen equipos o partidos
        if ($num_equipos > 0 || $partidos_data['total'] > 0) {
            $categories[] = [
                'ID' => $cat['ID'],
                'Nom' => $cat['Nom'],
                'num_equipos' => $num_equipos,
                'num_partidos' => $partidos_data['total'],
                'partidos_jugados' => $partidos_data['jugados'],
                'partidos_programados' => $partidos_data['programados']
            ];
        }
    }
} catch(PDOException $e) {
    $categories = [];
}

// Obtener estadísticas generales del sistema (CORREGIDAS)
try {
    $general_stats = $pdo->query("
        SELECT 
            COUNT(DISTINCT c.ID) as total_categorias,
            COUNT(DISTINCT CASE 
                WHEN e.Nom NOT IN ('1R INFANTIL MASCULÍ GRUP 1', '1R INFANTIL MASCULÍ GRUP 2', '1R CLASSIFICAT', '2N CLASSIFICAT') 
                THEN e.ID 
            END) as total_equipos,
            (SELECT COUNT(*) FROM resultats) as total_partidos,
            (SELECT COUNT(*) FROM resultats WHERE PuntsLocal > 0 OR PuntsVisitant > 0) as total_jugados
        FROM categoria c 
        LEFT JOIN equips e ON c.ID = e.Categoria 
        WHERE c.Nom NOT LIKE '%FINALS%'
    ")->fetch();
} catch(PDOException $e) {
    $general_stats = [
        'total_categorias' => 0,
        'total_equipos' => 0,
        'total_partidos' => 0,
        'total_jugados' => 0
    ];
}
?>
<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liga 3x3 2025 - Resultats i Classificacions</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/index.css">
</head>
<body>
    <header>
    <div class="header-content">
        <h1>3x3 - Memorial Joan Hernández Gómez</h1>
        <div class="club-info">
            <img src="./imgs/LogoCBLF.png" alt="CB Les Franqueses Logo" class="club-logo">
            <h1>CB Les Franqueses</h1>
        </div>
        <h3>Clasificacions i Resultats</h3>
    </div>
</header>

    <nav class="navbar">
        <div class="nav-container">
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="./web/calendario.php" class="nav-link">
                        <i class="fas fa-calendar-alt"></i> <span>Calendari</span>
                    </a>
                </li>
                <li class="nav-item active">
                    <a href="index.php" class="nav-link">
                        <i class="fas fa-trophy"></i> <span>Resultats</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="./web/reglament.php" class="nav-link">
                        <i class="fas fa-book"></i> <span>Reglament</span>
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="page-title">
            <h2><i class="fas fa-list-alt"></i> Selecciona una Categoria</h2>
            <p>Consulta els resultats, classificacions i estadístiques de cada categoria</p>
        </div>

        <!-- Estadísticas generales del sistema -->
        <div style="background: rgba(255, 255, 255, 0.95); border-radius: 20px; padding: 25px; margin-bottom: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
            <h3 style="color: #4CAF50; text-align: center; margin-bottom: 20px;">
                <i class="fas fa-chart-bar"></i> Resum de la Lliga
            </h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 20px;">
                <div style="text-align: center; padding: 15px; background: linear-gradient(135deg, #f8f9fa, #e9ecef); border-radius: 12px;">
                    <div style="font-size: 2.2em; font-weight: bold; color: #4CAF50;"><?php echo $general_stats['total_categorias']; ?></div>
                    <div style="font-size: 0.9em; color: #6c757d; text-transform: uppercase; letter-spacing: 1px;">Categories</div>
                </div>
                <div style="text-align: center; padding: 15px; background: linear-gradient(135deg, #f8f9fa, #e9ecef); border-radius: 12px;">
                    <div style="font-size: 2.2em; font-weight: bold; color: #4CAF50;"><?php echo $general_stats['total_equipos']; ?></div>
                    <div style="font-size: 0.9em; color: #6c757d; text-transform: uppercase; letter-spacing: 1px;">Equips</div>
                </div>
                <div style="text-align: center; padding: 15px; background: linear-gradient(135deg, #f8f9fa, #e9ecef); border-radius: 12px;">
                    <div style="font-size: 2.2em; font-weight: bold; color: #4CAF50;"><?php echo $general_stats['total_partidos']; ?></div>
                    <div style="font-size: 0.9em; color: #6c757d; text-transform: uppercase; letter-spacing: 1px;">Partits</div>
                </div>
                <div style="text-align: center; padding: 15px; background: linear-gradient(135deg, #f8f9fa, #e9ecef); border-radius: 12px;">
                    <div style="font-size: 2.2em; font-weight: bold; color: #4CAF50;"><?php echo $general_stats['total_jugados']; ?></div>
                    <div style="font-size: 0.9em; color: #6c757d; text-transform: uppercase; letter-spacing: 1px;">Jugats</div>
                </div>
            </div>
        </div>

        <div class="categories">
            <?php if (!empty($categories)): ?>
                <?php foreach ($categories as $category): ?>
                    <div class="category">
                        <?php
                        // Determinar el enlace según la categoría
                        $category_link = '';
                        switch ($category['Nom']) {
                            case 'PREMINI MIXTE':
                                $category_link = './web/premini.php';
                                break;
                            case 'MINI MASCULÍ':
                                $category_link = './web/mini.php';
                                break;
                            case 'INFANTIL MASCULÍ GRUP 1':
                                $category_link = './web/infantil1.php';
                                break;
                            case 'INFANTIL MASCULÍ GRUP 2':
                                $category_link = './web/infantil2.php';
                                break;
                            case 'INFANTIL FEMENÍ':
                                $category_link = './web/infantil_femeni.php';
                                break;
                            case 'CADET MASCULÍ':
                                $category_link = './web/cadet.php';
                                break;
                            case 'SENIOR MASCULÍ':
                                $category_link = './web/senior.php';
                                break;
                            case 'SENIOR FEMENÍ':
                                $category_link = './web/senior_femeni.php';
                                break;
                            case 'VETERANS MASCULÍ':
                                $category_link = './web/veterans.php';
                                break;
                            default:
                                $category_link = 'categoria.php?id=' . $category['ID'];
                                break;
                        }
                        ?>
                        <a href="<?php echo $category_link; ?>" class="category-link">
                            <h2><?php echo htmlspecialchars($category['Nom']); ?></h2>
                            
                            <div class="category-stats">
                                <div class="stat-item">
                                    <span class="stat-number"><?php echo $category['num_equipos']; ?></span>
                                    <span class="stat-label">Equips</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-number"><?php echo $category['num_partidos']; ?></span>
                                    <span class="stat-label">Partits</span>
                                </div>
                            </div>
                            
                            <!-- Información adicional sobre estado de partidos -->
                            <?php if ($category['num_partidos'] > 0): ?>
                                <div style="margin-top: 15px; padding: 10px; background: rgba(76,175,80,0.1); border-radius: 8px; font-size: 0.85em;">
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <span style="color: #28a745; font-weight: bold;">
                                            <i class="fas fa-check-circle"></i> <?php echo $category['partidos_jugados']; ?> jugats
                                        </span>
                                        <?php if ($category['partidos_programados'] > 0): ?>
                                            <span style="color: #ffc107; font-weight: bold;">
                                                <i class="fas fa-clock"></i> <?php echo $category['partidos_programados']; ?> programats
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-categories">
                    <i class="fas fa-basketball-ball"></i>
                    <h3>No hay categorías disponibles</h3>
                    <p>Las categorías se mostrarán aquí cuando estén configuradas</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <footer>
        <p>&copy; 3x3 - Memorial Joan Hernández Gómez</p>
    </footer>
<script>
// Aplicar colores específicos por categoría
document.addEventListener('DOMContentLoaded', function() {
    console.log('🎨 Aplicando colores por categoría...');
    
    const categories = document.querySelectorAll('.category');
    
    categories.forEach((category, index) => {
        const h2 = category.querySelector('h2');
        const statNumbers = category.querySelectorAll('.stat-number');
        
        if (h2) {
            const text = h2.textContent.trim();
            console.log(`Categoría ${index + 1}: "${text}"`);
            
            let colorClass = '';
            let colors = {};
            
            // Determinar colores según el texto - COLORES ACTUALIZADOS
            if (text.includes('PREMINI')) {
                colorClass = 'color-premini';
                colors = {
                    main: '#FFD700',
                    gradient: 'linear-gradient(90deg, #FFFF00, #FFD700, #FFFF80)',
                    border: 'rgba(255, 255, 0, 0.5)'
                };
            } else if (text.includes('MINI MASCULÍ')) {
                colorClass = 'color-mini';
                colors = {
                    main: '#D2B48C', // RGB(252, 213, 180)
                    gradient: 'linear-gradient(90deg, #FCD5B4, #D2691E, #F5DEB3)',
                    border: 'rgba(252, 213, 180, 0.7)'
                };
            } else if (text.includes('INFANTIL MASCULÍ GRUP 1')) {
                colorClass = 'color-infantil1';
                colors = {
                    main: '#A3C1E8', // RGB(197, 217, 241)
                    gradient: 'linear-gradient(90deg, #C5D9F1, #1976D2, #90CAF9)',
                    border: 'rgba(197, 217, 241, 0.7)'
                };
            } else if (text.includes('INFANTIL MASCULÍ GRUP 2')) {
                colorClass = 'color-infantil2';
                colors = {
                    main: '#A3C1E8', // RGB(197, 217, 241) - mismo que Grup 1
                    gradient: 'linear-gradient(90deg, #C5D9F1, #1976D2, #90CAF9)',
                    border: 'rgba(197, 217, 241, 0.7)'
                };
            } else if (text.includes('INFANTIL FEMENÍ')) {
                colorClass = 'color-infantil_femeni';
                colors = {
                    main: '#92D050', // RGB(146, 208, 80)
                    gradient: 'linear-gradient(90deg, #92D050, #4CAF50, #A5D6A7)',
                    border: 'rgba(146, 208, 80, 0.7)'
                };
            } else if (text.includes('CADET')) {
                colorClass = 'color-cadet';
                colors = {
                    main: '#FF0000', // RGB(255, 0, 0)
                    gradient: 'linear-gradient(90deg, #FF0000, #DC143C, #FF6B6B)',
                    border: 'rgba(255, 0, 0, 0.5)'
                };
            } else if (text.includes('SENIOR MASCULÍ')) {
                colorClass = 'color-senior';
                colors = {
                    main: '#538ED5', // RGB(83, 142, 213)
                    gradient: 'linear-gradient(90deg, #538ED5, #1976D2, #90CAF9)',
                    border: 'rgba(83, 142, 213, 0.7)'
                };
            } else if (text.includes('SENIOR FEMENÍ')) {
                colorClass = 'color-senior_femeni';
                colors = {
                    main: '#948B54', // RGB(148, 139, 84)
                    gradient: 'linear-gradient(90deg, #948B54, #8D6E63, #BCAAA4)',
                    border: 'rgba(148, 139, 84, 0.7)'
                };
            } else if (text.includes('VETERANS')) {
                colorClass = 'color-veterans';
                colors = {
                    main: '#4F6228', // RGB(79, 98, 40)
                    gradient: 'linear-gradient(90deg, #4F6228, #2E7D32, #66BB6A)',
                    border: 'rgba(79, 98, 40, 0.7)'
                };
            }
            
            if (colorClass && colors.main) {
                console.log(`  → Aplicando color: ${colors.main}`);
                
                // Aplicar clase
                category.classList.add(colorClass);
                
                // Aplicar estilos directamente (fallback)
                h2.style.color = colors.main;
                
                // Aplicar color a los números de estadísticas
                statNumbers.forEach(statNumber => {
                    statNumber.style.color = colors.main;
                });
                
                // Aplicar gradiente al ::before
                const beforeElement = category.querySelector('::before');
                if (beforeElement) {
                    beforeElement.style.background = colors.gradient;
                } else {
                    // Crear elemento pseudo manualmente
                    const pseudoBefore = document.createElement('div');
                    pseudoBefore.style.cssText = `
                        position: absolute;
                        top: 0;
                        left: 0;
                        right: 0;
                        height: 4px;
                        background: ${colors.gradient};
                        content: '';
                        z-index: 1;
                    `;
                    category.insertBefore(pseudoBefore, category.firstChild);
                }
                
                // Aplicar hover personalizado
                category.addEventListener('mouseenter', function() {
                    this.style.border = `2px solid ${colors.border}`;
                });
                
                category.addEventListener('mouseleave', function() {
                    this.style.border = '2px solid transparent';
                });
            }
        }
    });
    
    console.log('✅ Colores aplicados');
});

// Resto del JavaScript existente
document.querySelectorAll('.category').forEach(category => {
    category.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-8px) scale(1.02)';
    });
    
    category.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0) scale(1)';
    });
});

// Animación de carga
window.addEventListener('load', function() {
    document.body.style.opacity = '1';
});

// Debug info para desarrollo
if (window.location.hostname === 'localhost') {
    console.log('🏀 Liga 3x3 2025 - Sistema actualizado');
    console.log('📊 Estadísticas generales:', {
        categorias: <?php echo $general_stats['total_categorias']; ?>,
        equipos: <?php echo $general_stats['total_equipos']; ?>,
        partidos: <?php echo $general_stats['total_partidos']; ?>,
        jugados: <?php echo $general_stats['total_jugados']; ?>
    });
    
    // Log de categorías con datos
    console.log('📈 Categorías disponibles:');
    <?php foreach ($categories as $cat): ?>
        console.log('- <?php echo addslashes($cat['Nom']); ?>: <?php echo $cat['num_partidos']; ?> partidos, <?php echo $cat['num_equipos']; ?> equipos');
    <?php endforeach; ?>
}
</script>
</body>
</html>