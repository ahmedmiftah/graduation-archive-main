# 📚 توثيق التحديثات — نظام أرشفة مشاريع التخرج

> **التاريخ:** يوليو 2026  
> **المشروع:** نظام أرشفة مشاريع التخرج — كلية التقنية الإلكترونية  
> **التقنيات المستخدمة:** Laravel 12 + Inertia.js + Vue 3 + TypeScript

---

## 📌 فهرس التحديثات

1. [تحسين البحث الشامل](#1-تحسين-البحث-الشامل)
2. [إضافة فئة الدرجة العلمية](#2-إضافة-فئة-الدرجة-العلمية)
3. [حماية ملفات المشاريع من الجمهور](#3-حماية-ملفات-المشاريع-من-الجمهور)

---

## 1. تحسين البحث الشامل

### 📝 الوصف
قبل هذا التحديث، كان البحث يعمل فقط على حقل **عنوان المشروع** و**الوصف**. بعد التحديث، أصبح البحث يشمل تلقائياً عدة حقول في نفس الوقت.

### ✅ حقول البحث الجديدة
| الحقل | الوصف |
|---|---|
| `project_title` | عنوان المشروع (كان موجوداً) |
| `description` | وصف المشروع (كان موجوداً) |
| `department.name` | **اسم القسم** (جديد) |
| `specialization.name` | **اسم التخصص** (جديد) |
| `supervisor.name` | **اسم المشرف** (جديد) |

### 📁 الملفات التي تم تعديلها

#### [MODIFY] `app/Services/SearchService.php`
```php
// البحث الشامل في جميع الحقول المرتبطة
$query->where(function ($q) use ($search) {
    $q->where('project_title', 'like', "%{$search}%")
      ->orWhere('description', 'like', "%{$search}%")
      ->orWhereHas('department', function ($q) use ($search) {
          $q->where('name', 'like', "%{$search}%");
      })
      ->orWhereHas('specialization', function ($q) use ($search) {
          $q->where('name', 'like', "%{$search}%");
      })
      ->orWhereHas('supervisor', function ($q) use ($search) {
          $q->where('name', 'like', "%{$search}%");
      });
});
```
> **الاستخدام:** لوحة تحكم المشاريع (`/projects`) + صفحة التصفح للجمهور (`/browse`)

#### [MODIFY] `app/Http/Controllers/PublicController.php`
نفس منطق البحث الشامل أُضيف هنا أيضاً للصفحة العامة.

---

## 2. إضافة فئة الدرجة العلمية

### 📝 الوصف
إضافة حقل جديد `degree_level` (الفئة / الدرجة العلمية) يسمح بتصنيف المشاريع إلى ثلاث فئات:

| القيمة في قاعدة البيانات | العرض للمستخدم |
|---|---|
| `diploma` | دبلوم 🟠 |
| `bachelor` | بكالوريوس 🔵 |
| `master` | ماجستير 🟣 |

### 📁 الملفات التي تم تعديلها

---

#### [NEW] Migration — قاعدة البيانات
**الملف:** `database/migrations/2026_07_14_195836_add_degree_level_to_projects_table.php`

```php
// إضافة الحقل إلى جدول projects
$table->enum('degree_level', ['diploma', 'bachelor', 'master'])
      ->default('bachelor')
      ->after('academic_year');
```
> ✅ **تم تطبيقه** بالأمر `php artisan migrate`

---

#### [MODIFY] `app/Models/Project.php`
```php
protected $fillable = [
    // ...
    'degree_level',  // ← مضاف جديد
    // ...
];
```

---

#### [MODIFY] `app/Http/Requests/StoreProjectRequest.php`
```php
'degree_level' => ['required', 'string', 'in:diploma,bachelor,master'],
```
> **الاستخدام:** التحقق من البيانات عند **إنشاء** مشروع جديد

---

#### [MODIFY] `app/Http/Requests/UpdateProjectRequest.php`
```php
'degree_level' => ['required', 'string', 'in:diploma,bachelor,master'],
```
> **الاستخدام:** التحقق من البيانات عند **تعديل** مشروع

---

#### [MODIFY] `app/Http/Controllers/ProjectController.php`
```php
// في index() — قبول الفلتر
$filters = $request->only([
    'search', 'department_id', 'specialization_id',
    'academic_year', 'supervisor_id', 'degree_level', // ← جديد
    'status', 'sort',
]);

// في store() و update() — حفظ القيمة
$project = Project::create([
    // ...
    'degree_level' => $data['degree_level'], // ← جديد
]);
```

---

#### [MODIFY] `app/Services/SearchService.php`
```php
// فلترة حسب الفئة
if (! empty($filters['degree_level'])) {
    $query->where('degree_level', $filters['degree_level']);
}
```

---

#### [MODIFY] `app/Http/Controllers/PublicController.php`
```php
// فلترة حسب الفئة في الصفحة العامة
if ($degreeLevel = $request->input('degree_level')) {
    $query->where('degree_level', $degreeLevel);
}
```

---

#### [MODIFY] `resources/js/components/FilterPanel.vue`
أُضيف فلتر "الفئة" إلى لوحة **الفلاتر المتقدمة** في لوحة التحكم.

```html
<!-- Degree Level -->
<div class="flex flex-col gap-1">
    <label>الفئة</label>
    <select v-model="local.degree_level">
        <option value="">كل الفئات</option>
        <option value="diploma">دبلوم</option>
        <option value="bachelor">بكالوريوس</option>
        <option value="master">ماجستير</option>
    </select>
</div>
```
> **الموقع:** لوحة التحكم ← قائمة المشاريع ← "الفلاتر المتقدمة"

---

#### [MODIFY] `resources/js/pages/Projects/Index.vue`
- أُضيف **عمود "الفئة"** في جدول المشاريع بلوحة التحكم مع ألوان مميزة لكل فئة.
- أُضيف `degree_level` ضمن الفلاتر النشطة (الـ chips).

```html
<!-- عمود الفئة في الجدول -->
<span :class="[
    'rounded-full px-2 py-0.5 text-xs font-medium',
    project.degree_level === 'master'  ? 'bg-purple-100 text-purple-700' :
    project.degree_level === 'diploma' ? 'bg-orange-100 text-orange-700' :
                                         'bg-blue-100 text-blue-700'
]">
    {{ project.degree_level === 'diploma' ? 'دبلوم' : (project.degree_level === 'master' ? 'ماجستير' : 'بكالوريوس') }}
</span>
```
> **الموقع:** `/projects` ← جدول المشاريع

---

#### [MODIFY] `resources/js/pages/Projects/Create.vue`
أُضيف حقل اختيار الفئة في نموذج إضافة مشروع جديد.

> **الموقع:** `/projects/create` ← حقل "الفئة (الدرجة العلمية)"

---

#### [MODIFY] `resources/js/pages/Projects/Edit.vue`
أُضيف حقل اختيار الفئة في نموذج تعديل المشروع مع تحميل قيمة الفئة الحالية تلقائياً.

> **الموقع:** `/projects/{id}/edit` ← حقل "الفئة (الدرجة العلمية)"

---

#### [MODIFY] `resources/js/pages/Public/Browse.vue`
- أُضيفت قائمة منسدلة لاختيار الفئة ضمن فلاتر البحث.
- تظهر الفئة الآن على كل بطاقة مشروع في شبكة العرض.

> **الموقع:** `/browse` ← شريط الفلاتر + بطاقات المشاريع

---

## 3. حماية ملفات المشاريع من الجمهور

### 📝 الوصف
قبل التحديث، كان أي زائر للموقع يستطيع تحميل ملف PDF للمشروع مباشرة. بعد التحديث:

- ✅ **يرى الجمهور:** ملخص المشروع النصي (الوصف) بالكامل.
- 🔒 **يرى الجمهور بدل التحميل:** إشعار بأن الملف الكامل متاح فقط للمستخدمين المسجّلين مع رابط لصفحة تسجيل الدخول.

### 📁 الملف الذي تم تعديله

#### [MODIFY] `resources/js/pages/Public/Show.vue`

**قبل:**
```html
<!-- زر تحميل الملف — كان متاحاً للجميع ❌ -->
<a :href="'/storage/' + project.draft_file_path" target="_blank">
    تحميل الملف
</a>
```

**بعد:**
```html
<!-- قسم ملخص المشروع ✅ -->
<div v-if="project.description">
    <h2>ملخص المشروع</h2>
    <p>{{ project.description }}</p>
</div>

<!-- إشعار القفل للملف الكامل 🔒 -->
<div v-if="project.draft_file_path">
    <p>الملف الكامل للمشروع</p>
    <p>متاح فقط للمستخدمين المسجّلين —
        <Link :href="route('login')">تسجيل الدخول</Link>
    </p>
</div>
```

> **الموقع:** `/browse/{id}` ← صفحة تفاصيل المشروع للجمهور

---

## 🗂️ ملخص الملفات المعدّلة

| الملف | نوع التعديل | الغرض |
|---|---|---|
| `database/migrations/..._add_degree_level.php` | 🆕 جديد | إضافة حقل الفئة لقاعدة البيانات |
| `app/Models/Project.php` | ✏️ تعديل | إضافة `degree_level` للـ fillable |
| `app/Http/Requests/StoreProjectRequest.php` | ✏️ تعديل | قاعدة تحقق للفئة عند الإنشاء |
| `app/Http/Requests/UpdateProjectRequest.php` | ✏️ تعديل | قاعدة تحقق للفئة عند التعديل |
| `app/Http/Controllers/ProjectController.php` | ✏️ تعديل | قبول وحفظ الفئة + فلتر البحث |
| `app/Http/Controllers/PublicController.php` | ✏️ تعديل | بحث شامل + فلتر الفئة + إرجاع الفلتر |
| `app/Services/SearchService.php` | ✏️ تعديل | بحث شامل + فلتر `degree_level` |
| `resources/js/components/FilterPanel.vue` | ✏️ تعديل | قائمة فلترة الفئة في الفلاتر المتقدمة |
| `resources/js/pages/Projects/Index.vue` | ✏️ تعديل | عمود الفئة + فلتر + chip |
| `resources/js/pages/Projects/Create.vue` | ✏️ تعديل | حقل اختيار الفئة عند الإنشاء |
| `resources/js/pages/Projects/Edit.vue` | ✏️ تعديل | حقل اختيار الفئة عند التعديل |
| `resources/js/pages/Public/Browse.vue` | ✏️ تعديل | فلتر الفئة + عرضها في البطاقات |
| `resources/js/pages/Public/Show.vue` | ✏️ تعديل | إزالة التحميل + عرض الملخص + قفل الملف |

---

## 🚀 أوامر التشغيل

```bash
# 1. تحديث قاعدة البيانات (تم بالفعل ✅)
php artisan migrate

# 2. تشغيل سيرفر Laravel
php artisan serve

# 3. تشغيل Vite (Frontend)
npm run dev
```

> **الرابط:** [http://127.0.0.1:8000](http://127.0.0.1:8000)
