<?php
/**
 * Sistema de Validación de Datos
 * Sistema de Aprobación Multi-Área - Universidad Católica
 */

namespace UC\ApprovalSystem\Utils;

class Validator 
{
    private $data = [];
    private $rules = [];
    private $errors = [];
    private $messages = [];
    
    /**
     * Constructor
     */
    public function __construct(array $data = []) 
    {
        $this->data = $data;
        $this->initializeMessages();
    }
    
    /**
     * Crear nueva instancia del validador
     */
    public static function make(array $data, array $rules): self 
    {
        $validator = new self($data);
        $validator->rules = $rules;
        return $validator;
    }
    
    /**
     * Ejecutar validación
     */
    public function validate(): bool 
    {
        $this->errors = [];
        
        foreach ($this->rules as $field => $rules) {
            $this->validateField($field, $rules);
        }
        
        return empty($this->errors);
    }
    
    /**
     * Validar campo específico
     */
    private function validateField(string $field, $rules): void 
    {
        if (is_string($rules)) {
            $rules = explode('|', $rules);
        }
        
        $value = $this->getValue($field);
        
        foreach ($rules as $rule) {
            $this->applyRule($field, $rule, $value);
        }
    }
    
    /**
     * Aplicar regla de validación
     */
    private function applyRule(string $field, string $rule, $value): void 
    {
        // Parsear regla con parámetros
        $ruleParts = explode(':', $rule, 2);
        $ruleName = $ruleParts[0];
        $parameters = isset($ruleParts[1]) ? explode(',', $ruleParts[1]) : [];
        
        $methodName = 'validate' . ucfirst($ruleName);
        
        if (method_exists($this, $methodName)) {
            $result = call_user_func_array([$this, $methodName], array_merge([$field, $value], $parameters));
            
            if (!$result) {
                $this->addError($field, $ruleName, $parameters);
            }
        } else {
            Logger::warning('Regla de validación no encontrada', [
                'rule' => $ruleName,
                'field' => $field
            ]);
        }
    }
    
    /**
     * Obtener valor del campo
     */
    private function getValue(string $field) 
    {
        return $this->data[$field] ?? null;
    }
    
    /**
     * Agregar error de validación
     */
    private function addError(string $field, string $rule, array $parameters = []): void 
    {
        $message = $this->getMessage($field, $rule, $parameters);
        
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        
        $this->errors[$field][] = $message;
    }
    
    /**
     * Obtener mensaje de error
     */
    private function getMessage(string $field, string $rule, array $parameters = []): string 
    {
        $key = "{$field}.{$rule}";
        
        if (isset($this->messages[$key])) {
            $message = $this->messages[$key];
        } elseif (isset($this->messages[$rule])) {
            $message = $this->messages[$rule];
        } else {
            $message = "El campo {$field} no es válido";
        }
        
        // Reemplazar placeholders
        $message = str_replace(':field', $field, $message);
        
        foreach ($parameters as $index => $parameter) {
            $message = str_replace(':param' . ($index + 1), $parameter, $message);
        }
        
        return $message;
    }
    
    /**
     * Obtener errores de validación
     */
    public function errors(): array 
    {
        return $this->errors;
    }
    
    /**
     * Verificar si hay errores
     */
    public function fails(): bool 
    {
        return !empty($this->errors);
    }
    
    /**
     * Verificar si la validación pasó
     */
    public function passes(): bool 
    {
        return empty($this->errors);
    }
    
    /**
     * Obtener primer error de un campo
     */
    public function first(string $field): ?string 
    {
        return $this->errors[$field][0] ?? null;
    }
    
    /**
     * Establecer mensajes personalizados
     */
    public function setMessages(array $messages): self 
    {
        $this->messages = array_merge($this->messages, $messages);
        return $this;
    }
    
    // ========================================
    // REGLAS DE VALIDACIÓN
    // ========================================
    
    /**
     * Validar campo requerido
     */
    protected function validateRequired(string $field, $value): bool 
    {
        if (is_null($value)) {
            return false;
        }
        
        if (is_string($value) && trim($value) === '') {
            return false;
        }
        
        if (is_array($value) && empty($value)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Validar email
     */
    protected function validateEmail(string $field, $value): bool 
    {
        if (is_null($value) || $value === '') {
            return true; // Permitir vacío si no es requerido
        }
        
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Validar longitud mínima
     */
    protected function validateMin(string $field, $value, int $min): bool 
    {
        if (is_null($value)) {
            return true;
        }
        
        if (is_string($value)) {
            return strlen($value) >= $min;
        }
        
        if (is_numeric($value)) {
            return $value >= $min;
        }
        
        if (is_array($value)) {
            return count($value) >= $min;
        }
        
        return false;
    }
    
    /**
     * Validar longitud máxima
     */
    protected function validateMax(string $field, $value, int $max): bool 
    {
        if (is_null($value)) {
            return true;
        }
        
        if (is_string($value)) {
            return strlen($value) <= $max;
        }
        
        if (is_numeric($value)) {
            return $value <= $max;
        }
        
        if (is_array($value)) {
            return count($value) <= $max;
        }
        
        return false;
    }
    
    /**
     * Validar longitud exacta
     */
    protected function validateSize(string $field, $value, int $size): bool 
    {
        if (is_null($value)) {
            return true;
        }
        
        if (is_string($value)) {
            return strlen($value) === $size;
        }
        
        if (is_numeric($value)) {
            return $value == $size;
        }
        
        if (is_array($value)) {
            return count($value) === $size;
        }
        
        return false;
    }
    
    /**
     * Validar que sea numérico
     */
    protected function validateNumeric(string $field, $value): bool 
    {
        if (is_null($value) || $value === '') {
            return true;
        }
        
        return is_numeric($value);
    }
    
    /**
     * Validar entero
     */
    protected function validateInteger(string $field, $value): bool 
    {
        if (is_null($value) || $value === '') {
            return true;
        }
        
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }
    
    /**
     * Validar decimal/float
     */
    protected function validateDecimal(string $field, $value): bool 
    {
        if (is_null($value) || $value === '') {
            return true;
        }
        
        return filter_var($value, FILTER_VALIDATE_FLOAT) !== false;
    }
    
    /**
     * Validar que esté en lista de valores
     */
    protected function validateIn(string $field, $value, ...$options): bool 
    {
        if (is_null($value) || $value === '') {
            return true;
        }
        
        return in_array($value, $options);
    }
    
    /**
     * Validar que NO esté en lista de valores
     */
    protected function validateNotIn(string $field, $value, ...$options): bool 
    {
        if (is_null($value) || $value === '') {
            return true;
        }
        
        return !in_array($value, $options);
    }
    
    /**
     * Validar expresión regular
     */
    protected function validateRegex(string $field, $value, string $pattern): bool 
    {
        if (is_null($value) || $value === '') {
            return true;
        }
        
        return preg_match($pattern, $value) === 1;
    }
    
    /**
     * Validar fecha
     */
    protected function validateDate(string $field, $value): bool 
    {
        if (is_null($value) || $value === '') {
            return true;
        }
        
        return Helper::isValidDate($value, 'Y-m-d');
    }
    
    /**
     * Validar fecha y hora
     */
    protected function validateDatetime(string $field, $value): bool 
    {
        if (is_null($value) || $value === '') {
            return true;
        }
        
        return Helper::isValidDate($value, 'Y-m-d H:i:s');
    }
    
    /**
     * Validar fecha después de otra
     */
    protected function validateAfter(string $field, $value, string $afterField): bool 
    {
        if (is_null($value) || $value === '') {
            return true;
        }
        
        $afterValue = $this->getValue($afterField);
        
        if (is_null($afterValue)) {
            return false;
        }
        
        try {
            $date = new \DateTime($value);
            $afterDate = new \DateTime($afterValue);
            return $date > $afterDate;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Validar fecha antes de otra
     */
    protected function validateBefore(string $field, $value, string $beforeField): bool 
    {
        if (is_null($value) || $value === '') {
            return true;
        }
        
        $beforeValue = $this->getValue($beforeField);
        
        if (is_null($beforeValue)) {
            return false;
        }
        
        try {
            $date = new \DateTime($value);
            $beforeDate = new \DateTime($beforeValue);
            return $date < $beforeDate;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Validar URL
     */
    protected function validateUrl(string $field, $value): bool 
    {
        if (is_null($value) || $value === '') {
            return true;
        }
        
        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }
    
    /**
     * Validar RUT chileno
     */
    protected function validateRut(string $field, $value): bool 
    {
        if (is_null($value) || $value === '') {
            return true;
        }
        
        return Helper::isValidRut($value);
    }
    
    /**
     * Validar teléfono chileno
     */
    protected function validatePhone(string $field, $value): bool 
    {
        if (is_null($value) || $value === '') {
            return true;
        }
        
        // Limpiar teléfono
        $phone = preg_replace('/[^0-9]/', '', $value);
        
        // Validar formato chileno
        if (strlen($phone) === 8) {
            return true; // Móvil sin 9
        }
        
        if (strlen($phone) === 9 && (substr($phone, 0, 1) === '9' || substr($phone, 0, 1) === '2')) {
            return true; // Móvil con 9 o fijo Santiago
        }
        
        if (strlen($phone) === 11 && substr($phone, 0, 2) === '56') {
            return true; // Con código país
        }
        
        return false;
    }
    
    /**
     * Validar archivo
     */
    protected function validateFile(string $field, $value): bool 
    {
        if (is_null($value)) {
            return true;
        }
        
        if (!is_array($value)) {
            return false;
        }
        
        return isset($value['tmp_name']) && 
               isset($value['error']) && 
               $value['error'] === UPLOAD_ERR_OK &&
               is_uploaded_file($value['tmp_name']);
    }
    
    /**
     * Validar imagen
     */
    protected function validateImage(string $field, $value): bool 
    {
        if (!$this->validateFile($field, $value)) {
            return false;
        }
        
        if (is_null($value)) {
            return true;
        }
        
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $extension = Helper::getFileExtension($value['name']);
        
        return in_array($extension, $imageExtensions);
    }
    
    /**
     * Validar extensión de archivo
     */
    protected function validateMimes(string $field, $value, ...$extensions): bool 
    {
        if (!$this->validateFile($field, $value)) {
            return false;
        }
        
        if (is_null($value)) {
            return true;
        }
        
        $extension = Helper::getFileExtension($value['name']);
        return in_array($extension, $extensions);
    }
    
    /**
     * Validar tamaño máximo de archivo
     */
    protected function validateMaxFileSize(string $field, $value, int $maxSize): bool 
    {
        if (!$this->validateFile($field, $value)) {
            return false;
        }
        
        if (is_null($value)) {
            return true;
        }
        
        return $value['size'] <= $maxSize;
    }
    
    /**
     * Validar confirmación (ej: password_confirmation)
     */
    protected function validateConfirmed(string $field, $value): bool 
    {
        $confirmationField = $field . '_confirmation';
        $confirmationValue = $this->getValue($confirmationField);
        
        return $value === $confirmationValue;
    }
    
    /**
     * Validar que sea diferente a otro campo
     */
    protected function validateDifferent(string $field, $value, string $otherField): bool 
    {
        $otherValue = $this->getValue($otherField);
        return $value !== $otherValue;
    }
    
    /**
     * Validar que sea igual a otro campo
     */
    protected function validateSame(string $field, $value, string $otherField): bool 
    {
        $otherValue = $this->getValue($otherField);
        return $value === $otherValue;
    }
    
    /**
     * Validar string alfabético
     */
    protected function validateAlpha(string $field, $value): bool 
    {
        if (is_null($value) || $value === '') {
            return true;
        }
        
        return preg_match('/^[a-zA-ZáéíóúñÁÉÍÓÚÑ\s]+$/', $value) === 1;
    }
    
    /**
     * Validar string alfanumérico
     */
    protected function validateAlphaNum(string $field, $value): bool 
    {
        if (is_null($value) || $value === '') {
            return true;
        }
        
        return preg_match('/^[a-zA-Z0-9áéíóúñÁÉÍÓÚÑ\s]+$/', $value) === 1;
    }
    
    /**
     * Validar entre dos valores
     */
    protected function validateBetween(string $field, $value, $min, $max): bool 
    {
        if (is_null($value) || $value === '') {
            return true;
        }
        
        if (is_numeric($value)) {
            return $value >= $min && $value <= $max;
        }
        
        if (is_string($value)) {
            $length = strlen($value);
            return $length >= $min && $length <= $max;
        }
        
        return false;
    }
    
    /**
     * Validar booleano
     */
    protected function validateBoolean(string $field, $value): bool 
    {
        if (is_null($value) || $value === '') {
            return true;
        }
        
        return in_array($value, [true, false, 1, 0, '1', '0', 'true', 'false'], true);
    }
    
    /**
     * Validar JSON válido
     */
    protected function validateJson(string $field, $value): bool 
    {
        if (is_null($value) || $value === '') {
            return true;
        }
        
        json_decode($value);
        return json_last_error() === JSON_ERROR_NONE;
    }
    
    /**
     * Validar dirección IP
     */
    protected function validateIp(string $field, $value): bool 
    {
        if (is_null($value) || $value === '') {
            return true;
        }
        
        return filter_var($value, FILTER_VALIDATE_IP) !== false;
    }
    
    /**
     * Validar IPv4
     */
    protected function validateIpv4(string $field, $value): bool 
    {
        if (is_null($value) || $value === '') {
            return true;
        }
        
        return filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
    }
    
    /**
     * Validar IPv6
     */
    protected function validateIpv6(string $field, $value): bool 
    {
        if (is_null($value) || $value === '') {
            return true;
        }
        
        return filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
    }
    
    // ========================================
    // VALIDACIONES ESPECÍFICAS DEL SISTEMA
    // ========================================
    
    /**
     * Validar código de proyecto
     */
    protected function validateProjectCode(string $field, $value): bool 
    {
        if (is_null($value) || $value === '') {
            return true;
        }
        
        return preg_match('/^PROJ-\d{4}-\d{3}$/', $value) === 1;
    }
    
    /**
     * Validar prioridad del proyecto
     */
    protected function validatePriority(string $field, $value): bool 
    {
        if (is_null($value) || $value === '') {
            return true;
        }
        
        return in_array($value, ['low', 'medium', 'high', 'critical']);
    }
    
    /**
     * Validar estado del proyecto
     */
    protected function validateStatus(string $field, $value): bool 
    {
        if (is_null($value) || $value === '') {
            return true;
        }
        
        return in_array($value, ['draft', 'submitted', 'in_review', 'approved', 'rejected', 'on_hold']);
    }
    
    /**
     * Validar área del sistema
     */
    protected function validateArea(string $field, $value): bool 
    {
        if (is_null($value) || $value === '') {
            return true;
        }
        
        $areas = array_keys(Helper::config('areas', []));
        return in_array($value, $areas);
    }
    
    /**
     * Validar rol de usuario
     */
    protected function validateRole(string $field, $value): bool 
    {
        if (is_null($value) || $value === '') {
            return true;
        }
        
        return in_array($value, ['user', 'reviewer', 'supervisor', 'admin']);
    }
    
    // ========================================
    // MÉTODOS DE UTILIDAD
    // ========================================
    
    /**
     * Inicializar mensajes por defecto
     */
    private function initializeMessages(): void 
    {
        $this->messages = [
            'required' => 'El campo :field es obligatorio',
            'email' => 'El campo :field debe ser un email válido',
            'min' => 'El campo :field debe tener al menos :param1 caracteres',
            'max' => 'El campo :field no puede tener más de :param1 caracteres',
            'size' => 'El campo :field debe tener exactamente :param1 caracteres',
            'numeric' => 'El campo :field debe ser numérico',
            'integer' => 'El campo :field debe ser un número entero',
            'decimal' => 'El campo :field debe ser un número decimal',
            'in' => 'El campo :field debe ser uno de los valores permitidos',
            'not_in' => 'El campo :field no puede ser uno de los valores prohibidos',
            'regex' => 'El formato del campo :field no es válido',
            'date' => 'El campo :field debe ser una fecha válida',
            'datetime' => 'El campo :field debe ser una fecha y hora válida',
            'after' => 'El campo :field debe ser posterior a :param1',
            'before' => 'El campo :field debe ser anterior a :param1',
            'url' => 'El campo :field debe ser una URL válida',
            'rut' => 'El campo :field debe ser un RUT válido',
            'phone' => 'El campo :field debe ser un teléfono chileno válido',
            'file' => 'El campo :field debe ser un archivo válido',
            'image' => 'El campo :field debe ser una imagen válida',
            'mimes' => 'El campo :field debe ser un archivo de tipo: :param1',
            'max_file_size' => 'El archivo :field no puede superar :param1 bytes',
            'confirmed' => 'La confirmación del campo :field no coincide',
            'different' => 'El campo :field debe ser diferente a :param1',
            'same' => 'El campo :field debe ser igual a :param1',
            'alpha' => 'El campo :field solo puede contener letras',
            'alpha_num' => 'El campo :field solo puede contener letras y números',
            'between' => 'El campo :field debe estar entre :param1 y :param2',
            'boolean' => 'El campo :field debe ser verdadero o falso',
            'json' => 'El campo :field debe ser un JSON válido',
            'ip' => 'El campo :field debe ser una dirección IP válida',
            'ipv4' => 'El campo :field debe ser una dirección IPv4 válida',
            'ipv6' => 'El campo :field debe ser una dirección IPv6 válida',
            'project_code' => 'El campo :field debe tener el formato PROJ-YYYY-XXX',
            'priority' => 'El campo :field debe ser: low, medium, high o critical',
            'status' => 'El campo :field debe ser un estado válido del proyecto',
            'area' => 'El campo :field debe ser un área válida del sistema',
            'role' => 'El campo :field debe ser un rol válido del sistema'
        ];
    }
    
    /**
     * Validación rápida estática
     */
    public static function quickValidate(array $data, array $rules): array 
    {
        $validator = self::make($data, $rules);
        $validator->validate();
        
        return [
            'valid' => $validator->passes(),
            'errors' => $validator->errors()
        ];
    }
    
    /**
     * Validar proyecto
     */
    public static function validateProject(array $data): array 
    {
        $rules = [
            'title' => 'required|min:3|max:255',
            'description' => 'required|min:10',
            'priority' => 'required|priority',
            'estimated_completion_date' => 'date',
            'budget' => 'numeric|min:0',
            'department' => 'required|min:2|max:100',
            'technical_lead' => 'max:255',
            'business_owner' => 'max:255'
        ];
        
        return self::quickValidate($data, $rules);
    }
    
    /**
     * Validar usuario
     */
    public static function validateUser(array $data): array 
    {
        $rules = [
            'email' => 'required|email|max:255',
            'name' => 'required|min:2|max:255',
            'first_name' => 'max:100',
            'last_name' => 'max:100',
            'department' => 'max:100',
            'title' => 'max:100',
            'phone' => 'phone',
            'employee_id' => 'max:50',
            'student_id' => 'max:50'
        ];
        
        return self::quickValidate($data, $rules);
    }
    
    /**
     * Validar administrador
     */
    public static function validateAdmin(array $data): array 
    {
        $rules = [
            'email' => 'required|email|max:255',
            'name' => 'required|min:2|max:255',
            'role' => 'required|role'
        ];
        
        return self::quickValidate($data, $rules);
    }
    
    /**
     * Validar documento
     */
    public static function validateDocument(array $data): array 
    {
        $rules = [
            'project_id' => 'required|integer|min:1',
            'area_name' => 'required|area',
            'document_name' => 'required|min:3|max:255',
            'file' => 'required|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,zip|max_file_size:10485760'
        ];
        
        return self::quickValidate($data, $rules);
    }
    
    /**
     * Validar feedback
     */
    public static function validateFeedback(array $data): array 
    {
        $rules = [
            'project_id' => 'required|integer|min:1',
            'feedback_text' => 'required|min:10',
            'feedback_type' => 'required|in:comment,requirement,suggestion,warning,error',
            'priority' => 'required|priority'
        ];
        
        return self::quickValidate($data, $rules);
    }
    
    /**
     * Validar configuración
     */
    public static function validateSettings(array $data): array 
    {
        $rules = [
            'setting_key' => 'required|alpha_num|max:100',
            'setting_value' => 'required',
            'setting_type' => 'required|in:string,integer,boolean,json,text',
            'category' => 'max:50'
        ];
        
        return self::quickValidate($data, $rules);
    }
}