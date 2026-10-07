<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Support\Facades\DB;

/**
 * 全モデル共通の基底クラス。
 * Rails 版と同じく timestamp(6) にマイクロ秒まで保存する。
 */
abstract class Model extends EloquentModel
{
    protected $dateFormat = 'Y-m-d H:i:s.u';

    /**
     * Rails の update_column 相当。コールバック・updated_at の更新を行わずに 1 カラムだけ更新する。
     */
    public function updateColumn(string $column, mixed $value): void
    {
        DB::table($this->getTable())->where($this->getKeyName(), $this->getKey())->update([$column => $value]);

        $this->setAttribute($column, $value);
        $this->syncOriginalAttribute($column);
    }
}
