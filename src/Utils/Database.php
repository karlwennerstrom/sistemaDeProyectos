<?php
/**
 * Clase de Conexión a Base de Datos
 * Sistema de Aprobación Multi-Área - Universidad Católica
 */

namespace UC\ApprovalSystem\Utils;

use PDO;
use PDOException;
use PDOStatement;

class Database 
{
    private static $instance = null;
    private $connection;
    private $config;
    private $transactionLevel = 0;
    
    /**
     * Constructor privado para implementar Singleton
     */
    private function __construct() 
    {
        $this->config = include __DIR__ . '/../../config/database.php';
        $this->connect();
    }
    
    /**
     * Obtener instancia única de la base de datos
     */
    public static function getInstance(): Database 
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Obtener conexión PDO
     */
    public function getConnection(): PDO 
    {
        if ($this->connection === null) {
            $this->connect();
        }
        return $this->connection;
    }
    
    /**
     * Conectar a la base de datos
     */
    private function connect(): void 
    {
        try {
            $connectionName = $this->config['default'];
            $config = $this->config['connections'][$connectionName];
            
            $dsn = $this->buildDsn($config);
            
            $this->connection = new PDO(
                $dsn,
                $config['username'],
                $config['password'],
                $config['options'] ?? []
            );
            
            // Configurar timezone
            $this->connection->exec("SET time_zone = '-03:00'"); // Chile timezone
            
            Logger::info('Conexión a base de datos establecida', [
                'host' => $config['host'],
                'database' => $config['database']
            ]);
            
        } catch (PDOException $e) {
            Logger::error('Error conectando a base de datos: ' . $e->getMessage());
            throw new \Exception('Error de conexión a base de datos: ' . $e->getMessage());
        }
    }
    
    /**
     * Construir DSN para la conexión
     */
    private function buildDsn(array $config): string 
    {
        $dsn = $config['driver'] . ':';
        $dsn .= 'host=' . $config['host'] . ';';
        $dsn .= 'port=' . $config['port'] . ';';
        $dsn .= 'dbname=' . $config['database'] . ';';
        $dsn .= 'charset=' . $config['charset'];
        
        return $dsn;
    }
    
    /**
     * Ejecutar consulta SELECT
     */
    public function select(string $query, array $params = []): array 
    {
        try {
            $stmt = $this->prepare($query);
            $stmt->execute($params);
            
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            Logger::debug('Query SELECT ejecutada', [
                'query' => $query,
                'params' => $params,
                'results' => count($result)
            ]);
            
            return $result;
            
        } catch (PDOException $e) {
            Logger::error('Error en SELECT: ' . $e->getMessage(), [
                'query' => $query,
                'params' => $params
            ]);
            throw $e;
        }
    }
    
    /**
     * Ejecutar consulta SELECT y retornar solo un registro
     */
    public function selectOne(string $query, array $params = []): ?array 
    {
        $results = $this->select($query, $params);
        return !empty($results) ? $results[0] : null;
    }
    
    /**
     * Ejecutar consulta INSERT
     */
    public function insert(string $query, array $params = []): int 
    {
        try {
            $stmt = $this->prepare($query);
            $stmt->execute($params);
            
            $lastInsertId = (int)$this->connection->lastInsertId();
            
            Logger::debug('Query INSERT ejecutada', [
                'query' => $query,
                'params' => $params,
                'lastInsertId' => $lastInsertId
            ]);
            
            return $lastInsertId;
            
        } catch (PDOException $e) {
            Logger::error('Error en INSERT: ' . $e->getMessage(), [
                'query' => $query,
                'params' => $params
            ]);
            throw $e;
        }
    }
    
    /**
     * Ejecutar consulta UPDATE
     */
    public function update(string $query, array $params = []): int 
    {
        try {
            $stmt = $this->prepare($query);
            $stmt->execute($params);
            
            $affectedRows = $stmt->rowCount();
            
            Logger::debug('Query UPDATE ejecutada', [
                'query' => $query,
                'params' => $params,
                'affectedRows' => $affectedRows
            ]);
            
            return $affectedRows;
            
        } catch (PDOException $e) {
            Logger::error('Error en UPDATE: ' . $e->getMessage(), [
                'query' => $query,
                'params' => $params
            ]);
            throw $e;
        }
    }
    
    /**
     * Ejecutar consulta DELETE
     */
    public function delete(string $query, array $params = []): int 
    {
        try {
            $stmt = $this->prepare($query);
            $stmt->execute($params);
            
            $affectedRows = $stmt->rowCount();
            
            Logger::debug('Query DELETE ejecutada', [
                'query' => $query,
                'params' => $params,
                'affectedRows' => $affectedRows
            ]);
            
            return $affectedRows;
            
        } catch (PDOException $e) {
            Logger::error('Error en DELETE: ' . $e->getMessage(), [
                'query' => $query,
                'params' => $params
            ]);
            throw $e;
        }
    }
    
    /**
     * Ejecutar cualquier consulta SQL
     */
    public function execute(string $query, array $params = []): PDOStatement 
    {
        try {
            $stmt = $this->prepare($query);
            $stmt->execute($params);
            
            Logger::debug('Query ejecutada', [
                'query' => $query,
                'params' => $params
            ]);
            
            return $stmt;
            
        } catch (PDOException $e) {
            Logger::error('Error ejecutando query: ' . $e->getMessage(), [
                'query' => $query,
                'params' => $params
            ]);
            throw $e;
        }
    }
    
    /**
     * Preparar consulta SQL
     */
    public function prepare(string $query): PDOStatement 
    {
        return $this->connection->prepare($query);
    }
    
    /**
     * Iniciar transacción
     */
    public function beginTransaction(): void 
    {
        if ($this->transactionLevel === 0) {
            $this->connection->beginTransaction();
            Logger::debug('Transacción iniciada');
        }
        $this->transactionLevel++;
    }
    
    /**
     * Confirmar transacción
     */
    public function commit(): void 
    {
        $this->transactionLevel--;
        if ($this->transactionLevel === 0) {
            $this->connection->commit();
            Logger::debug('Transacción confirmada');
        }
    }
    
    /**
     * Cancelar transacción
     */
    public function rollback(): void 
    {
        if ($this->transactionLevel > 0) {
            $this->connection->rollback();
            $this->transactionLevel = 0;
            Logger::debug('Transacción cancelada');
        }
    }
    
    /**
     * Ejecutar dentro de una transacción
     */
    public function transaction(callable $callback) 
    {
        $this->beginTransaction();
        
        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (\Exception $e) {
            $this->rollback();
            Logger::error('Error en transacción: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Insertar múltiples registros de forma eficiente
     */
    public function insertBatch(string $table, array $data): int 
    {
        if (empty($data)) {
            return 0;
        }
        
        $keys = array_keys($data[0]);
        $placeholders = '(' . implode(',', array_fill(0, count($keys), '?')) . ')';
        $values = implode(',', array_fill(0, count($data), $placeholders));
        
        $query = "INSERT INTO {$table} (" . implode(',', $keys) . ") VALUES {$values}";
        
        $params = [];
        foreach ($data as $row) {
            foreach ($keys as $key) {
                $params[] = $row[$key] ?? null;
            }
        }
        
        try {
            $stmt = $this->prepare($query);
            $stmt->execute($params);
            
            $affectedRows = $stmt->rowCount();
            
            Logger::debug('Batch INSERT ejecutado', [
                'table' => $table,
                'records' => count($data),
                'affectedRows' => $affectedRows
            ]);
            
            return $affectedRows;
            
        } catch (PDOException $e) {
            Logger::error('Error en batch INSERT: ' . $e->getMessage(), [
                'table' => $table,
                'records' => count($data)
            ]);
            throw $e;
        }
    }
    
    /**
     * Verificar si una tabla existe
     */
    public function tableExists(string $table): bool 
    {
        $query = "SHOW TABLES LIKE ?";
        $result = $this->select($query, [$table]);
        return !empty($result);
    }
    
    /**
     * Obtener información de columnas de una tabla
     */
    public function getTableColumns(string $table): array 
    {
        $query = "DESCRIBE {$table}";
        return $this->select($query);
    }
    
    /**
     * Ejecutar un archivo SQL
     */
    public function executeSqlFile(string $filepath): bool 
    {
        if (!file_exists($filepath)) {
            throw new \Exception("Archivo SQL no encontrado: {$filepath}");
        }
        
        $sql = file_get_contents($filepath);
        $statements = array_filter(
            array_map('trim', explode(';', $sql))
        );
        
        $this->beginTransaction();
        
        try {
            foreach ($statements as $statement) {
                if (!empty($statement)) {
                    $this->connection->exec($statement);
                }
            }
            
            $this->commit();
            
            Logger::info('Archivo SQL ejecutado exitosamente', [
                'file' => $filepath,
                'statements' => count($statements)
            ]);
            
            return true;
            
        } catch (PDOException $e) {
            $this->rollback();
            Logger::error('Error ejecutando archivo SQL: ' . $e->getMessage(), [
                'file' => $filepath
            ]);
            throw $e;
        }
    }
    
    /**
     * Obtener estadísticas de la base de datos
     */
    public function getStats(): array 
    {
        $tables = $this->select("SHOW TABLES");
        $stats = [
            'tables' => count($tables),
            'table_details' => []
        ];
        
        foreach ($tables as $table) {
            $tableName = array_values($table)[0];
            $count = $this->selectOne("SELECT COUNT(*) as count FROM {$tableName}");
            $stats['table_details'][$tableName] = $count['count'];
        }
        
        return $stats;
    }
    
    /**
     * Crear backup de la base de datos
     */
    public function createBackup(string $filepath = null): string 
    {
        if ($filepath === null) {
            $filepath = 'backups/backup_' . date('Y-m-d_H-i-s') . '.sql';
        }
        
        $config = $this->config['connections'][$this->config['default']];
        
        $command = sprintf(
            'mysqldump --host=%s --port=%s --user=%s --password=%s %s > %s',
            escapeshellarg($config['host']),
            escapeshellarg($config['port']),
            escapeshellarg($config['username']),
            escapesharg($config['password']),
            escapeshellarg($config['database']),
            escapeshellarg($filepath)
        );
        
        $result = shell_exec($command);
        
        if (file_exists($filepath)) {
            Logger::info('Backup creado exitosamente', ['file' => $filepath]);
            return $filepath;
        } else {
            throw new \Exception('Error creando backup de base de datos');
        }
    }
    
    /**
     * Obtener último error de MySQL
     */
    public function getLastError(): ?string 
    {
        $errorInfo = $this->connection->errorInfo();
        return $errorInfo[2] ?? null;
    }
    
    /**
     * Destructor para cerrar conexión
     */
    public function __destruct() 
    {
        $this->connection = null;
    }
    
    /**
     * Prevenir clonación del singleton
     */
    private function __clone() {}
    
    /**
     * Prevenir deserialización del singleton
     */
    public function __wakeup() 
    {
        throw new \Exception("Cannot unserialize a singleton.");
    }
}