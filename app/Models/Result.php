<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Result extends Model
{
    protected $fillable = ['student_id', 'subject_id', 'term', 'academic_year', 'marks', 'grade', 'entered_by'];

    protected function casts(): array
    {
        return ['marks' => 'decimal:2'];
    }

    public static function gradeFor(float $marks): string
    {
        return match (true) {
            $marks >= 80 => 'A',
            $marks >= 70 => 'B',
            $marks >= 60 => 'C',
            $marks >= 50 => 'D',
            default => 'E',
        };
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}
