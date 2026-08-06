<?php

declare(strict_types=1);

namespace App\Core;

use PDOStatement;

/**
 * Trait para depuración de consultas SQL
 * 
 * Proporciona métodos para obtener la sentencia SQL completa
 * con los valores interpolados.
 * 
 * Uso:
 *   class MiClase {
 *       use Debugable;
 *       
 *       public function miMetodo(PDOStatement $stmt, array $params) {
 *           $this->dumpSql($stmt, $params);
 *       }
 *   }
 */
trait Debugable
{
    /**
     * Obtiene la consulta SQL completa con los valores interpolados
     * 
     * @param PDOStatement $stmt La sentencia preparada
     * @param array $params Los parámetros que se pasan a execute()
     * @return string La consulta SQL con los valores interpolados
     */
    protected function debugSql(PDOStatement $stmt, array $params): string
    {
        // Obtener la consulta SQL original
        $sql = $stmt->queryString;

        // Si no hay parámetros, devolver la consulta sin cambios
        if (empty($params)) {
            return $sql;
        }

        // Reemplazar cada parámetro por su valor
        foreach ($params as $key => $value) {
            // Determinar el valor a insertar (SIEMPRE como string)
            if (is_null($value)) {
                $replacement = 'NULL';
            } elseif (is_int($value) || is_float($value)) {
                $replacement = (string)$value; // ✅ Convertir a string
            } elseif (is_bool($value)) {
                $replacement = $value ? 'TRUE' : 'FALSE';
            } else {
                // Escapar comillas simples (para strings)
                $replacement = '"' . str_replace('"', '\"', (string)$value) . '"';
            }

            // Determinar el marcador de posición
            if (is_int($key) && $key >= 0) {
                // Parámetro posicional (?, ?, ?)
                $placeholder = '?';
            } else {
                // Parámetro nombrado (:nombre)
                $placeholder = strpos($key, ':') === 0 ? $key : ':' . $key;
            }

            // Reemplazar el marcador
            if ($placeholder === '?') {
                // Para parámetros posicionales, reemplazar el primer '?' encontrado
                $pos = strpos($sql, '?');
                if ($pos !== false) {
                    $sql = substr_replace($sql, $replacement, $pos, 1);
                }
            } else {
                // ✅ Asegurar que $replacement es string para str_replace
                $sql = str_replace($placeholder, (string)$replacement, $sql);
            }
        }

        return $sql;
    }

    /**
     * Muestra la sentencia SQL en la salida estándar
     * 
     * @param PDOStatement $stmt La sentencia preparada
     * @param array $params Los parámetros que se pasan a execute()
     * @param string $prefix Texto opcional antes de la sentencia
     */
    protected function dumpSql(PDOStatement $stmt, array $params, string $prefix = '[SQL] '): void
    {
        echo $prefix . $this->debugSql($stmt, $params) . "\n";
    }

    /**
     * Muestra la sentencia SQL con formato para depuración
     * (incluye información adicional como el archivo y la línea)
     * 
     * @param PDOStatement $stmt La sentencia preparada
     * @param array $params Los parámetros que se pasan a execute()
     * @param string $prefix Texto opcional antes de la sentencia
     */
    protected function debugSqlWithContext(PDOStatement $stmt, array $params, string $prefix = '[SQL] '): void
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $caller = $backtrace[1] ?? null;
        
        if ($caller) {
            $file = basename($caller['file'] ?? 'unknown');
            $line = $caller['line'] ?? '?';
            $context = "{$file}:{$line}";
        } else {
            $context = 'unknown';
        }
        
        echo $prefix . "[{$context}] " . $this->debugSql($stmt, $params) . "\n";
    }
}