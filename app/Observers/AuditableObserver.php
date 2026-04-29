<?php

namespace App\Observers;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditableObserver
{
    public function created(Model $model): void
    {
        $this->log('created', $model, [], $model->getAttributes());
    }

    public function updated(Model $model): void
    {
        $dirty = $model->getDirty();
        $old = [];
        foreach (array_keys($dirty) as $key) {
            $old[$key] = $model->getOriginal($key);
        }
        $this->log('updated', $model, $old, $dirty);
    }

    public function deleted(Model $model): void
    {
        $this->log('deleted', $model, $model->getAttributes(), []);
    }

    private function log(string $action, Model $model, array $old, array $new): void
    {
        $sensitive = ['password', 'remember_token'];
        foreach ($sensitive as $key) {
            unset($old[$key], $new[$key]);
        }

        $user = auth()->user();

        AuditLog::create([
            'user_id'        => $user?->id,
            'user_name'      => $user?->name,
            'user_role'      => $user?->roles->first()?->name,
            'action'         => $action,
            'auditable_type' => get_class($model),
            'auditable_id'   => (string) $model->getKey(),
            'model_label'    => class_basename($model),
            'old_values'     => empty($old) ? null : $old,
            'new_values'     => empty($new) ? null : $new,
            'ip_address'     => request()?->ip(),
            'user_agent'     => request()?->userAgent(),
            'created_at'     => now(),
        ]);
    }
}
