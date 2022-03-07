<?php

namespace Wahdam\LaravelSequence\Traits;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Wahdam\LaravelSequence\Models\Sequence;

trait HasSequence
{
    protected $prefix = 'YYYYMM';
    protected $reset = false;
    protected $resetDate = 'month';
    protected $length = 8;
    protected $maxTries = 5;

    public function getTableName()
    {
        return with(new static())->getTable();
    }

    public function hasConfig()
    {
        return Cache::remember(get_class(new static()), now()->addDays(30), function () {
            return $this->setConfig();
        });
    }

    public function nextVal()
    {
        if (! $this->hasConfig()) {
            $this->setConfig();
        }

        return DB::transaction(function () {
            try {
                $currentSequence = Sequence::where('sequence', get_class(new static()))->lockForUpdate()->first();

                $midCharacter = $this->identifier ?? '';

                if (is_null($currentSequence->current)) {
                    return 1;
                }

                if ($currentSequence->reset < today()) {
                    $currentSequence->current = 1;
                    $currentSequence->reset = $this->nextResetDate();
                    $currentSequence->save();
                    DB::commit();

                    return now()->isoFormat($currentSequence->prefix) . $midCharacter  . str_pad($currentSequence->current, $this->length, '0', STR_PAD_LEFT);
                }

                $currentSequence->increment('current', 1);
                $currentSequence->save();

                DB::commit();

                return now()->isoFormat($currentSequence->prefix) . $midCharacter . str_pad($currentSequence->current, $this->length, '0', STR_PAD_LEFT);
            } catch (\Throwable $th) {
                log('Error getting sequence '.$th->getMessage());
                DB::rollBack();
            }
        }, $this->maxTries);
    }

    public static function getSequence()
    {
        return with(new static())->nextVal();
    }

    public function setConfig()
    {
        return Sequence::firstOrCreate([
            'sequence' => get_class(new static()),
            'current' => 0,
            'prefix' => $this->prefix,
            'reset' => today()->addMonths(1),
            ])->exists();
    }

    public function nextResetDate()
    {
        switch ($this->resetDate) {
            case 'year':
            return today()->endOfMonth()->addYears(1);

                break;
            case 'month':
            return today()->endOfMonth()->addMonths(1);

                break;
            case 'day':
            return today()->endOfMonth()->addDays(1);

                break;

            default:
            return today()->endOfMonth()->addMonths(1);

                break;
        }
    }

    public static function bootHasSequence()
    {
        static::creating(function ($model) {
            $model->{$model->getKeyName()} = static::getSequence();
        });
    }
}
