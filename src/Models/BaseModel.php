<?php
/**
 * Modelo Base
 * Sistema de Aprobación Multi-Área - Universidad Católica
 */

namespace UC\ApprovalSystem\Models;

use UC\ApprovalSystem\Utils\Database;
use UC\ApprovalSystem\Utils\Logger;
use PDO;
use PDOException;

abstract class BaseModel 
{
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    protected $fillable = [];
    protected $guarded = ['id', 'created_at', 'updated_at'];
    protected $timestamps = true;
    protected $dateFormat = 'Y-m-d H:i:s';
    protected $attributes = [];
    protected $original = [];
    protected $exists = false;
    
    /**
     * Constructor
     */
    public function __construct(array $attributes = []) 
    {
        $this->db = Database::getInstance();
        $this->fill($attributes);
    }
    
    /**
     * Llenar el modelo con atributos
     */
    public function fill(array $attributes): self 
    {
        foreach ($attributes as $key => $value) {
            if ($this->isFillable($key)) {
                $this->setAttribute($key, $value);
            }
        }
        return $this;
    }
    
    /**
     * Verificar si un atributo es llenável
     */
    protected function isFillable(string $key): bool 
    {
        if (in_array($key, $this->guarded)) {
            return false;
        }
        
        if (empty($this->fillable)) {
            return true;
        }
        
        return in_array($key, $this->fillable);
    }
    
    /**
     * Establecer valor de atributo
     */
    public function setAttribute(string $key, $value): void 
    {
        $this->attributes[$key] = $value;
    }
    
    /**
     * Obtener valor de atributo
     */
    public function getAttribute(string $key) 
    {
        return $this->attributes[$key] ?? null;
    }
    
    /**
     * Verificar si existe un atributo
     */
    public function hasAttribute(string $key): bool 
    {
        return array_key_exists($key, $this->attributes);
    }
    
    /**
     * Magic getter
     */
    public function __get(string $key) 
    {
        return $this->getAttribute($key);
    }
    
    /**
     * Magic setter
     */
    public function __set(string $key, $value): void 
    {
        $this->setAttribute($key, $value);
    }
    
    /**
     * Magic isset
     */
    public function __isset(string $key): bool 
    {
        return $this->hasAttribute($key);
    }
    
    /**
     * Crear nuevo registro
     */
    public static function create(array $attributes): ?self 
    {
        $instance = new static($attributes);
        
        if ($instance->save()) {
            return $instance;
        }
        
        return null;
    }
    
    /**
     * Encontrar registro por ID
     */
    public static function find($id): ?self 
    {
        $instance = new static();
        
        $query = "SELECT * FROM {$instance->table} WHERE {$instance->primaryKey} = ? LIMIT 1";
        $result = $instance->db->selectOne($query, [$id]);
        
        if ($result) {
            $instance->fill($result);
            $instance->exists = true;
            $instance->syncOriginal();
            return $instance;
        }
        
        return null;
    }
    
    /**
     * Encontrar registro por ID o fallar
     */
    public static function findOrFail($id): self 
    {
        $result = static::find($id);
        
        if (!$result) {
            throw new \Exception("Registro no encontrado con ID: {$id}");
        }
        
        return $result;
    }
    
    /**
     * Encontrar primer registro que coincida
     */
    public static function where(string $column, $operator, $value = null)
    {
        $instance = new static();
        return (new QueryBuilder($instance))->where($column, $operator, $value);
    }
    
    /**
     * Obtener todos los registros
     */
    public static function all(): array 
    {
        $instance = new static();
        $query = "SELECT * FROM {$instance->table}";
        $results = $instance->db->select($query);
        
        return array_map(function($row) {
            $model = new static($row);
            $model->exists = true;
            $model->syncOriginal();
            return $model;
        }, $results);
    }
    
    /**
     * Contar registros
     */
    public static function count(): int 
    {
        $instance = new static();
        $query = "SELECT COUNT(*) as count FROM {$instance->table}";
        $result = $instance->db->selectOne($query);
        
        return (int) ($result['count'] ?? 0);
    }
    
    /**
     * Guardar el modelo
     */
    public function save(): bool 
    {
        try {
            if ($this->exists) {
                return $this->performUpdate();
            } else {
                return $this->performInsert();
            }
        } catch (PDOException $e) {
            Logger::error('Error guardando modelo: ' . $e->getMessage(), [
                'model' => static::class,
                'attributes' => $this->attributes
            ]);
            return false;
        }
    }
    
    /**
     * Realizar inserción
     */
    protected function performInsert(): bool 
    {
        $attributes = $this->getAttributesForInsert();
        
        if ($this->timestamps) {
            $now = date($this->dateFormat);
            $attributes['created_at'] = $now;
            $attributes['updated_at'] = $now;
        }
        
        $columns = array_keys($attributes);
        $placeholders = array_fill(0, count($columns), '?');
        
        $query = "INSERT INTO {$this->table} (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
        
        $id = $this->db->insert($query, array_values($attributes));
        
        if ($id) {
            $this->setAttribute($this->primaryKey, $id);
            $this->exists = true;
            $this->syncOriginal();
            
            Logger::debug('Modelo insertado', [
                'model' => static::class,
                'id' => $id
            ]);
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Realizar actualización
     */
    protected function performUpdate(): bool 
    {
        $attributes = $this->getAttributesForUpdate();
        
        if (empty($attributes)) {
            return true; // No hay cambios
        }
        
        if ($this->timestamps) {
            $attributes['updated_at'] = date($this->dateFormat);
        }
        
        $sets = array_map(function($column) {
            return "{$column} = ?";
        }, array_keys($attributes));
        
        $query = "UPDATE {$this->table} SET " . implode(', ', $sets) . " WHERE {$this->primaryKey} = ?";
        
        $values = array_values($attributes);
        $values[] = $this->getAttribute($this->primaryKey);
        
        $affectedRows = $this->db->update($query, $values);
        
        if ($affectedRows >= 0) {
            $this->syncOriginal();
            
            Logger::debug('Modelo actualizado', [
                'model' => static::class,
                'id' => $this->getAttribute($this->primaryKey),
                'changes' => $attributes
            ]);
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Eliminar el modelo
     */
    public function delete(): bool 
    {
        if (!$this->exists) {
            return false;
        }
        
        try {
            $query = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?";
            $affectedRows = $this->db->delete($query, [$this->getAttribute($this->primaryKey)]);
            
            if ($affectedRows > 0) {
                $this->exists = false;
                
                Logger::debug('Modelo eliminado', [
                    'model' => static::class,
                    'id' => $this->getAttribute($this->primaryKey)
                ]);
                
                return true;
            }
            
            return false;
            
        } catch (PDOException $e) {
            Logger::error('Error eliminando modelo: ' . $e->getMessage(), [
                'model' => static::class,
                'id' => $this->getAttribute($this->primaryKey)
            ]);
            return false;
        }
    }
    
    /**
     * Obtener atributos para inserción
     */
    protected function getAttributesForInsert(): array 
    {
        $attributes = [];
        
        foreach ($this->attributes as $key => $value) {
            if (!in_array($key, $this->guarded)) {
                $attributes[$key] = $value;
            }
        }
        
        return $attributes;
    }
    
    /**
     * Obtener atributos para actualización
     */
    protected function getAttributesForUpdate(): array 
    {
        $attributes = [];
        
        foreach ($this->attributes as $key => $value) {
            if (!in_array($key, $this->guarded) && $this->isDirty($key)) {
                $attributes[$key] = $value;
            }
        }
        
        return $attributes;
    }
    
    /**
     * Verificar si un atributo ha cambiado
     */
    public function isDirty(string $key = null): bool 
    {
        if ($key === null) {
            return !empty($this->getDirty());
        }
        
        return array_key_exists($key, $this->attributes) && 
               (!array_key_exists($key, $this->original) || $this->original[$key] !== $this->attributes[$key]);
    }
    
    /**
     * Obtener atributos que han cambiado
     */
    public function getDirty(): array 
    {
        $dirty = [];
        
        foreach ($this->attributes as $key => $value) {
            if (!array_key_exists($key, $this->original) || $this->original[$key] !== $value) {
                $dirty[$key] = $value;
            }
        }
        
        return $dirty;
    }
    
    /**
     * Sincronizar atributos originales
     */
    public function syncOriginal(): void 
    {
        $this->original = $this->attributes;
    }
    
    /**
     * Refrescar el modelo desde la base de datos
     */
    public function refresh(): self 
    {
        if (!$this->exists) {
            return $this;
        }
        
        $fresh = static::find($this->getAttribute($this->primaryKey));
        
        if ($fresh) {
            $this->attributes = $fresh->attributes;
            $this->syncOriginal();
        }
        
        return $this;
    }
    
    /**
     * Convertir el modelo a array
     */
    public function toArray(): array 
    {
        return $this->attributes;
    }
    
    /**
     * Convertir el modelo a JSON
     */
    public function toJson(int $options = 0): string 
    {
        return json_encode($this->toArray(), $options);
    }
    
    /**
     * Obtener la clave primaria
     */
    public function getKey() 
    {
        return $this->getAttribute($this->primaryKey);
    }
    
    /**
     * Obtener el nombre de la clave primaria
     */
    public function getKeyName(): string 
    {
        return $this->primaryKey;
    }
    
    /**
     * Verificar si el modelo existe en la base de datos
     */
    public function exists(): bool 
    {
        return $this->exists;
    }
    
    /**
     * Obtener la tabla del modelo
     */
    public function getTable(): string 
    {
        return $this->table;
    }
    
    /**
     * Clonar el modelo
     */
    public function replicate(array $except = []): self 
    {
        $except = array_merge($except, [$this->primaryKey]);
        
        $attributes = array_diff_key($this->attributes, array_flip($except));
        
        $instance = new static($attributes);
        $instance->exists = false;
        
        return $instance;
    }
    
    /**
     * Actualizar el modelo con nuevos atributos
     */
    public function update(array $attributes): bool 
    {
        $this->fill($attributes);
        return $this->save();
    }
    
    /**
     * Incrementar un valor numérico
     */
    public function increment(string $column, int $amount = 1): bool 
    {
        $query = "UPDATE {$this->table} SET {$column} = {$column} + ? WHERE {$this->primaryKey} = ?";
        
        $affectedRows = $this->db->update($query, [$amount, $this->getKey()]);
        
        if ($affectedRows > 0) {
            $this->refresh();
            return true;
        }
        
        return false;
    }
    
    /**
     * Decrementar un valor numérico
     */
    public function decrement(string $column, int $amount = 1): bool 
    {
        return $this->increment($column, -$amount);
    }
    
    /**
     * Scope para filtrar por fecha de creación
     */
    public static function whereCreatedAt(string $operator, $date)
    {
        return static::where('created_at', $operator, $date);
    }
    
    /**
     * Scope para filtrar por fecha de actualización
     */
    public static function whereUpdatedAt(string $operator, $date)
    {
        return static::where('updated_at', $operator, $date);
    }
    
    /**
     * Scope para registros creados hoy
     */
    public static function createdToday()
    {
        return static::whereCreatedAt('>=', date('Y-m-d 00:00:00'));
    }
    
    /**
     * Scope para registros actualizados hoy
     */
    public static function updatedToday()
    {
        return static::whereUpdatedAt('>=', date('Y-m-d 00:00:00'));
    }
    
    /**
     * Magic method para serialización JSON
     */
    public function jsonSerialize(): array 
    {
        return $this->toArray();
    }
    
    /**
     * Magic method toString
     */
    public function __toString(): string 
    {
        return $this->toJson();
    }
}

/**
 * Query Builder para construir consultas de forma fluida
 */
class QueryBuilder 
{
    protected $model;
    protected $wheres = [];
    protected $orderBy = [];
    protected $limit;
    protected $offset;
    protected $joins = [];
    
    public function __construct(BaseModel $model) 
    {
        $this->model = $model;
    }
    
    /**
     * Agregar condición WHERE
     */
    public function where(string $column, $operator, $value = null): self 
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }
        
        $this->wheres[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => 'and'
        ];
        
        return $this;
    }
    
    /**
     * Agregar condición WHERE con OR
     */
    public function orWhere(string $column, $operator, $value = null): self 
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }
        
        $this->wheres[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => 'or'
        ];
        
        return $this;
    }
    
    /**
     * Agregar condición WHERE IN
     */
    public function whereIn(string $column, array $values): self 
    {
        $this->wheres[] = [
            'type' => 'in',
            'column' => $column,
            'values' => $values,
            'boolean' => 'and'
        ];
        
        return $this;
    }
    
    /**
     * Agregar condición WHERE BETWEEN
     */
    public function whereBetween(string $column, array $values): self 
    {
        $this->wheres[] = [
            'type' => 'between',
            'column' => $column,
            'values' => $values,
            'boolean' => 'and'
        ];
        
        return $this;
    }
    
    /**
     * Agregar condición WHERE NULL
     */
    public function whereNull(string $column): self 
    {
        $this->wheres[] = [
            'type' => 'null',
            'column' => $column,
            'boolean' => 'and'
        ];
        
        return $this;
    }
    
    /**
     * Agregar condición WHERE NOT NULL
     */
    public function whereNotNull(string $column): self 
    {
        $this->wheres[] = [
            'type' => 'not_null',
            'column' => $column,
            'boolean' => 'and'
        ];
        
        return $this;
    }
    
    /**
     * Agregar ORDER BY
     */
    public function orderBy(string $column, string $direction = 'asc'): self 
    {
        $this->orderBy[] = [
            'column' => $column,
            'direction' => strtolower($direction)
        ];
        
        return $this;
    }
    
    /**
     * Agregar LIMIT
     */
    public function limit(int $value): self 
    {
        $this->limit = $value;
        return $this;
    }
    
    /**
     * Agregar OFFSET
     */
    public function offset(int $value): self 
    {
        $this->offset = $value;
        return $this;
    }
    
    /**
     * Ejecutar consulta y obtener primer resultado
     */
    public function first(): ?BaseModel 
    {
        $this->limit(1);
        $results = $this->get();
        
        return !empty($results) ? $results[0] : null;
    }
    
    /**
     * Ejecutar consulta y obtener todos los resultados
     */
    public function get(): array 
    {
        $sql = $this->buildSelect();
        $bindings = $this->getBindings();
        
        $results = $this->model->db->select($sql, $bindings);
        
        return array_map(function($row) {
            $model = new (get_class($this->model))($row);
            $model->exists = true;
            $model->syncOriginal();
            return $model;
        }, $results);
    }
    
    /**
     * Contar resultados
     */
    public function count(): int 
    {
        $sql = $this->buildCount();
        $bindings = $this->getBindings();
        
        $result = $this->model->db->selectOne($sql, $bindings);
        
        return (int) ($result['count'] ?? 0);
    }
    
    /**
     * Construir consulta SELECT
     */
    protected function buildSelect(): string 
    {
        $sql = "SELECT * FROM {$this->model->getTable()}";
        
        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . $this->buildWheres();
        }
        
        if (!empty($this->orderBy)) {
            $sql .= ' ORDER BY ' . $this->buildOrderBy();
        }
        
        if ($this->limit !== null) {
            $sql .= " LIMIT {$this->limit}";
        }
        
        if ($this->offset !== null) {
            $sql .= " OFFSET {$this->offset}";
        }
        
        return $sql;
    }
    
    /**
     * Construir consulta COUNT
     */
    protected function buildCount(): string 
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->model->getTable()}";
        
        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . $this->buildWheres();
        }
        
        return $sql;
    }
    
    /**
     * Construir cláusulas WHERE
     */
    protected function buildWheres(): string 
    {
        $sql = [];
        
        foreach ($this->wheres as $index => $where) {
            $boolean = $index === 0 ? '' : ' ' . strtoupper($where['boolean']) . ' ';
            
            switch ($where['type']) {
                case 'basic':
                    $sql[] = $boolean . "{$where['column']} {$where['operator']} ?";
                    break;
                case 'in':
                    $placeholders = implode(',', array_fill(0, count($where['values']), '?'));
                    $sql[] = $boolean . "{$where['column']} IN ({$placeholders})";
                    break;
                case 'between':
                    $sql[] = $boolean . "{$where['column']} BETWEEN ? AND ?";
                    break;
                case 'null':
                    $sql[] = $boolean . "{$where['column']} IS NULL";
                    break;
                case 'not_null':
                    $sql[] = $boolean . "{$where['column']} IS NOT NULL";
                    break;
            }
        }
        
        return implode('', $sql);
    }
    
    /**
     * Construir cláusulas ORDER BY
     */
    protected function buildOrderBy(): string 
    {
        $sql = [];
        
        foreach ($this->orderBy as $order) {
            $sql[] = "{$order['column']} {$order['direction']}";
        }
        
        return implode(', ', $sql);
    }
    
    /**
     * Obtener valores para binding
     */
    protected function getBindings(): array 
    {
        $bindings = [];
        
        foreach ($this->wheres as $where) {
            switch ($where['type']) {
                case 'basic':
                    $bindings[] = $where['value'];
                    break;
                case 'in':
                    $bindings = array_merge($bindings, $where['values']);
                    break;
                case 'between':
                    $bindings = array_merge($bindings, $where['values']);
                    break;
            }
        }
        
        return $bindings;
    }
}