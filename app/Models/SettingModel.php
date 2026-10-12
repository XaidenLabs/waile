<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Generic global settings store (key/value grouped by section).
 *
 *   $s = new SettingModel();
 *   $s->setValue('host', 'smtp.example.com', 'smtp');
 *   $host = $s->getValue('host', '', 'smtp');
 *   $all  = $s->getGroup('smtp');   // ['host' => ..., 'port' => ..., ...]
 *
 * Note: methods are named *Value (not get/set) to avoid clashing with the
 * reserved get()/set() methods on CodeIgniter\Model.
 */
class SettingModel extends Model
{
    protected $table         = 'settings';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = ['setting_group', 'setting_key', 'setting_value'];

    /**
     * Read a single setting value.
     */
    public function getValue(string $key, mixed $default = null, string $group = 'general'): mixed
    {
        $row = $this->where('setting_group', $group)
                    ->where('setting_key', $key)
                    ->first();

        return $row['setting_value'] ?? $default;
    }

    /**
     * Read every setting in a group as an associative array [key => value].
     */
    public function getGroup(string $group): array
    {
        $rows = $this->where('setting_group', $group)->findAll();

        $out = [];
        foreach ($rows as $r) {
            $out[$r['setting_key']] = $r['setting_value'];
        }

        return $out;
    }

    /**
     * Insert or update a single setting.
     */
    public function setValue(string $key, mixed $value, string $group = 'general'): bool
    {
        $existing = $this->where('setting_group', $group)
                         ->where('setting_key', $key)
                         ->first();

        if ($existing) {
            return $this->update($existing['id'], ['setting_value' => $value]);
        }

        return $this->insert([
            'setting_group' => $group,
            'setting_key'   => $key,
            'setting_value' => $value,
        ]) !== false;
    }

    /**
     * Bulk insert/update a group of settings from [key => value].
     */
    public function setGroup(string $group, array $values): void
    {
        foreach ($values as $key => $value) {
            $this->setValue($key, $value, $group);
        }
    }
}
