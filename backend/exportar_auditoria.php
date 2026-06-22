<?php
session_start();
require 'conexion.php';

if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'admin') {
    header("HTTP/1.1 403 Forbidden");
    exit();
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="Auditoria_InmobiliariaLibre_' . date('Y-m-d_H-i') . '.csv"');

$salida = fopen('php://output', 'w');

// Añadir marca UTF-8 BOM
fprintf($salida, chr(0xEF).chr(0xBB).chr(0xBF));

// Añadir la fila de encabezados
fputcsv($salida, ['Fecha y Hora', 'Usuario', 'Módulo', 'Acción Realizada'], ';');

try {
    $stmt = $pdo->prepare("SELECT fecha, usuario_nombre, modulo, accion FROM auditoria ORDER BY fecha DESC");
    $stmt->execute();
    
    while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // SOLUCIÓN EXCEL: Forzamos que Excel lo lea como texto agregando un espacio inicial 
        // y usando un formato más universal (Año-Mes-Día) para evitar que intente convertirlo.
        $fila['fecha'] = ' ' . date('Y-m-d H:i', strtotime($fila['fecha']));
        
        fputcsv($salida, $fila, ';');
    }
} catch (PDOException $e) {
    fputcsv($salida, ['Error al generar el reporte de auditoría: ' . $e->getMessage()], ';');
}

fclose($salida);
exit();
?>