<?php

use App\Models\FacultyMember;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

test('faculty member belongsTo academic degree', function () {
    expect((new FacultyMember())->degree())->toBeInstanceOf(BelongsTo::class);
});

test('faculty member belongsToMany departments', function () {
    expect((new FacultyMember())->departments())->toBeInstanceOf(BelongsToMany::class);
});

test('faculty member has supervised projects relationship', function () {
    expect((new FacultyMember())->supervisedProjects())->toBeInstanceOf(HasMany::class);
});

test('faculty member belongsToMany projects via project_faculty_members', function () {
    expect((new FacultyMember())->projects())->toBeInstanceOf(BelongsToMany::class);
});
