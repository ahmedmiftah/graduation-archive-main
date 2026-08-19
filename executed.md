# الأوامر المنفذة (Executed)

سجل توثيقي لكل مهمة تم تنفيذها فعلياً، منقولة من [plans_to_execute.md](plans_to_execute.md) بعد إنجازها.
كل بند هنا مرتبط برقم البند الأصلي في ملف الخطة.

---

## السجل

<!-- مثال:
### ✅ #1 إضافة عمود التقدير الملوّن في شاشة أرشيف المشاريع
- **تاريخ التنفيذ:** 2026-08-14
- **الملفات المتأثرة:** resources/js/pages/Projects/Archived/Index.vue
- **الشرح:** تمت إضافة عمود "التقدير" يعرض تصنيف الدرجة (ممتاز/جيد جداً/جيد/مقبول/ضعيف) بجانب عمود الدرجة الرقمية.
-->

### ✅ #1 شارة لونية لعمود "التقدير" في شاشة أرشيف المشاريع
- **تاريخ التنفيذ:** 2026-08-14
- **الملفات المتأثرة:** resources/js/pages/Projects/Archived/Index.vue
- **الشرح:** أضيفت دالة `gradeBadgeColor()` وتم تحويل خلية عمود "التقدير" من نص عادي إلى شارة ملوّنة (`rounded-full` badge): أخضر لـ"ممتاز"/"جيد جداً" (≥80)، أصفر لـ"جيد"/"مقبول" (≥60)، أحمر لـ"ضعيف" (<60)، رمادي عند عدم وجود درجة. تستخدم نفس أسلوب شارات الحالة الموجود في Projects/Index.vue.

### ✅ #2 رابط تنزيل PDF مباشر من صف الجدول في شاشة أرشيف المشاريع
- **تاريخ التنفيذ:** 2026-08-14
- **الملفات المتأثرة:** resources/js/pages/Projects/Archived/Index.vue
- **الشرح:** أُضيف عمود جديد "الملف" في جدول الأرشيف يحتوي رابط تنزيل مباشر (`↓ PDF`) لملف `draft_file_path` الخاص بكل مشروع عبر `/storage/{path}`، بدل الحاجة للدخول إلى تفاصيل المشروع أولاً. يظهر "—" عند عدم وجود ملف. حقل `draft_file_path` كان متوفراً بالفعل ضمن استجابة `SearchService::searchProjects()` دون الحاجة لتعديل الباك-إند. تم إضافة `draft_file_path` إلى واجهة `Project` في TypeScript وتحديث `colspan` من 6 إلى 7. تحقّق: اختبارات Pest ذات الصلة (11/11 ناجحة) وفحص `vue-tsc` بلا أخطاء.

### ✅ #3 حذف مسار "اعتماد المشروع" (approve) المتضارب مع أرشفة ArchiveProjectModal
- **تاريخ التنفيذ:** 2026-08-14
- **المشكلة:** كان هناك مساران منفصلان وغير متسقين لنقل مشروع إلى حالة "منجز" (archived): (1) `ArchiveProjectModal` عبر شاشة التعديل، يفرض ممتحنَين + درجة نهائية + ملف PDF قبل الأرشفة، و(2) زر "اعتماد المشروع" (`ProjectController::approve()`) الذي كان ينقل الحالة إلى `archived` مباشرة دون أي من هذه المتطلبات. تبيّن أن هذا المسار الثاني كان "ميتاً" فعلياً: `ProjectController::store()` يضع دائماً `current_status_id = STATUS_IN_PROGRESS` (5) عند إنشاء أي مشروع، ولا يوجد أي مسار حقيقي في النظام ينشئ مشروعاً بحالة `proposal_submitted` (2) — لذلك زر "اعتماد" لم يكن يظهر إطلاقاً في الواجهة. تم حذف المسار بالكامل.
- **الملفات المتأثرة:**
  - `app/Http/Controllers/ProjectController.php` — حذف الميثود `approve()` بالكامل
  - `routes/web.php` — حذف route `POST projects/{id}/approve`
  - `resources/js/pages/Projects/Index.vue` — حذف `canApprove()`، `approveProject()`، زر "اعتماد" من الجدول، ومدخل `proposal_submitted` من `STATUS_COLORS`/`STATUS_LABELS`
  - `resources/js/pages/Projects/Show.vue` — نفس الحذف (canApprove computed، approveProject، زر "اعتماد المشروع"، مدخل proposal_submitted)
  - `tests/Feature/Project/ProjectTest.php` — حذف اختباري "dept_manager can approve pending project" و"dept_staff cannot approve project"
  - `tests/Feature/Roles/RoleVerificationTest.php` — حذف 3 اختبارات ("super_admin can approve a pending project"، "dept_manager can approve a pending project"، "dept_staff cannot approve a project")
- **قرار محفوظ:** الفرع الخاص بـ `dept_staff` في `authorizeEdit()` الذي يتحقق من `STATUS_PENDING` (يسمح لـ dept_staff بتعديل مشروع بحالة `proposal_submitted` فقط) **لم يُحذف** — تُرك كما هو بناءً على طلب المستخدم، رغم أنه أيضاً "ميت" حالياً بنفس السبب (لا مسار ينشئ مشاريع بهذه الحالة). الثابت `STATUS_PENDING` و`pendingRvProject()` (test helper) أُبقيا لأنهما لا يزالان مُستخدَمين في اختبارات صلاحية `dept_staff` الصحيحة (`dept_staff can edit their own pending project...`، `dept_staff cannot edit pending project from a different department`).
- **التحقق:** `php artisan test --filter="ProjectTest|RoleVerificationTest"` → 71/71 ناجحة (كان 76 قبل الحذف، أي أُزيلت الاختبارات الخمسة المستهدفة بالضبط دون أي فشل آخر).

### ✅ #4 استبدال بطاقة "في انتظار الموافقة" في لوحة التحكم ببطاقة "مشاريع تحت التنفيذ"
- **تاريخ التنفيذ:** 2026-08-14
- **الملفات المتأثرة:** app/Services/ReportService.php، resources/js/pages/Dashboard.vue، tests/Feature/Report/ReportTest.php
- **الشرح:** استبدل `pending_approvals` (يحسب حالة `proposal_submitted` الميتة، دائماً 0) بـ `in_progress_count` (يحسب حالة `in_progress` رقم 5 — مقياس حقيقي يعكس العمل الجاري فعلياً). أُعيدت تسمية البطاقة إلى "مشاريع تحت التنفيذ" مع تغيير الأيقونة من `Clock` إلى `Loader2`. حُذف الثابت `STATUS_PROPOSAL` من `ReportService` بعد أن أصبح غير مستخدم، واستُبدل باسم `STATUS_IN_PROGRESS`. حُدّث اختبار `ReportTest` ليتحقق من `stats.in_progress_count` بدل `stats.pending_approvals`.

### ✅ #5 إضافة الفصل الدراسي لعمود السنة في "آخر المشاريع المضافة"
- **تاريخ التنفيذ:** 2026-08-14
- **الملفات المتأثرة:** app/Services/ReportService.php، resources/js/pages/Dashboard.vue
- **الشرح:** أُضيف عمود `semester` إلى استعلام `recent_projects` في `getDashboardStats()`، وأُضيف الحقل لواجهة `RecentProject` في TypeScript. الخلية تعرض الآن `${semester} ${academic_year}` (مثل: "ربيع 2026") بدل السنة فقط، بنفس نمط العرض المستخدم في شاشة أرشيف المشاريع.

### ✅ #6 إصلاح عمود "الحالة" في "آخر المشاريع المضافة" + توحيد شارات الحالة في ملف مشترك
- **تاريخ التنفيذ:** 2026-08-14
- **الملفات المتأثرة:** resources/js/lib/statusBadge.ts (جديد)، resources/js/pages/Dashboard.vue، resources/js/pages/Projects/Index.vue، resources/js/pages/Projects/Show.vue
- **الشرح:** استُخرجت خرائط `STATUS_COLORS`/`STATUS_LABELS` ودالتا `statusColor()`/`statusLabel()` (كانتا مكررتين حرفياً في Projects/Index.vue وProjects/Show.vue) إلى ملف مشترك واحد `resources/js/lib/statusBadge.ts`. استُبدلت النسختان المحليتان باستيراد من الملف المشترك في كلا الملفين. عمود "الحالة" في Dashboard.vue كان يعرض `status_name` الخام بالإنجليزية بشارة زرقاء ثابتة — أصبح الآن يستخدم نفس الدالتين المشتركتين لعرض تسمية عربية ولون حسب نوع الحالة (أخضر لـ"منجز"، أحمر لـ"مرفوض"...). تم توحيد تسمية `cancelled` على "ملغي" في كل الملفات (كانت "منقطع" في Show.vue فقط).

### ✅ #7 فلتر نطاق سنوات (من – إلى) في التقرير السنوي
- **تاريخ التنفيذ:** 2026-08-14
- **الملفات المتأثرة:** app/Services/ReportService.php، app/Http/Controllers/ReportController.php، resources/js/pages/Reports/Yearly.vue
- **الشرح:** `getYearlyComparisonReport()` أصبحت تقبل `?string $fromYear, ?string $toYear` اختياريين. نسبة النمو % تُحسب دائماً على أساس البيانات الكاملة غير المفلترة (بحيث لا يشوّه تضييق النطاق المعروض حسابات النمو)، ثم يُطبَّق الفلتر على قائمة العرض `yearly` وجدول `department_by_year` فقط. أُضيف مفتاح جديد `available_years` للاستجابة (كل السنوات المتاحة، لتعبئة قوائم الاختيار). `ReportController::yearlyReport()` يمرر `from_year`/`to_year` من الطلب، ونفس الفلتر يُمرَّر أيضاً عبر `exportPdf`/`exportExcel` (`buildExportData`/`yearlyExportData`) بحيث تصدير PDF/Excel يطابق النطاق المعروض في الشاشة. الواجهة أضافت قائمتي اختيار "من سنة"/"إلى سنة" مبنيتين من `available_years`، وزري "تطبيق" (`router.get` مع `preserveState`) و"إعادة تعيين" (يظهر فقط عند وجود فلتر نشط).

### ✅ #8 ترويسة/تذييل طباعة كاملة (شعار + اسم الكلية + رقم صفحة) عند الطباعة من المتصفح
- **تاريخ التنفيذ:** 2026-08-14
- **الملفات المتأثرة:** resources/js/components/ExportButtons.vue، resources/css/app.css
- **الشرح:** كتلة الشعار الخاصة بالطباعة (`print:block`) في `ExportButtons.vue` كانت تعرض صورة الشعار فقط — أُضيف اسم الكلية "كلية التقنية الإلكترونية" نصياً تحتها، بالإضافة إلى عنوان التقرير (`title`) ووصفه الفرعي (`subtitle`) إن تم تمريرهما من الصفحة (كل صفحات التقارير الأربعة تمررهما بالفعل، عدا Archived/Index.vue الذي يعرض الشعار واسم الكلية فقط). أُضيف تذييل رقم صفحة عبر `@page { @bottom-center { content: "صفحة " counter(page) " من " counter(pages); } }` في `app.css` — يعمل عبر آلية CSS Paged Media القياسية المدعومة في متصفحات Chromium (المتصفح المستهدف في بيئة العمل الحالية). زيد هامش الصفحة السفلي من 12mm إلى 18mm لإفساح مجال لنص رقم الصفحة.

### ✅ #10 توحيد "الممتحنين" و"المشرفين" في كيان واحد "أعضاء هيئة التدريس" + إضافة جدول الدرجات العلمية + حذف دوري المشرف والمشاهد
- **تاريخ التنفيذ:** 2026-08-14
- **الشرح:** إعادة هيكلة معمارية كبيرة نُفّذت بناءً على خطة مفصّلة (وليس بند من `plans_to_execute.md`). النظام كان يخلط بين "الممتحن" (`examiners`) ككيان بيانات منفصل، و"المشرف" (`supervisor`) كدور تسجيل دخول (`users` بدور Spatie)، رغم أن المشرف لا يقوم بأي عمليات CRUD فعلية. تم توحيدهما تحت كيان واحد **`faculty_members`** (أعضاء هيئة التدريس) بدون حساب دخول، يُستخدم مباشرة كمصدر لقوائم المشرفين والممتحنين معاً، وأُضيف جدول **`academic_degrees`** لإدارة الدرجات العلمية (بدل حقل `title` النصي الحر). عضو هيئة التدريس يمكن أن يتبع أكثر من قسم (علاقة M:N). حُذف دورا `supervisor` و`viewer` بالكامل من النظام (3 أدوار متبقية: `super_admin`, `dept_manager`, `dept_staff`).
- **قاعدة البيانات (migrations جديدة):**
  - `academic_degrees` (جديد): `degree_name`, `degree_code` (unique)
  - `examiners` → `faculty_members` (rename): حذف `title`/`department_id`، إضافة `phone_number`, `email`, `degree_id` (FK→`academic_degrees`, NOT NULL, `restrictOnDelete`)
  - `faculty_member_department` (جديد، M:N pivot)
  - `project_examiners` → `project_faculty_members` (rename)، `examiner_id` → `faculty_member_id`
  - `evaluations.examiner_id` → `faculty_member_id`
  - `projects.supervisor_id` و `project_proposals.supervisor_id`: تغيير الـ FK من `users` إلى `faculty_members`
  - ملاحظة تقنية: migration إعادة تسمية `project_examiners` واجهت خطأ MySQL 1553 (impossible to drop composite unique index still backing a FK) — الحل: إضافة العمود/الفهرس الجديدين أولاً قبل حذف القديمين، بحيث يبقى دائماً فهرس صالح لعمود `project_id`.
- **Models:** `AcademicDegree` (جديد)، `FacultyMember` (جديد، بديل `Examiner`)، `ProjectFacultyMember` (جديد، بديل `ProjectExaminer`)، تحديث `Department`/`Project`/`ProjectProposal`/`Evaluation`/`User` (حذف `supervisedProjects()` من `User` ونقلها إلى `FacultyMember`)
- **Backend:** `AcademicDegreeController` (super_admin فقط، index/store/destroy فقط)، `FacultyMemberController`، `ProjectFacultyMemberController` (بديل `ExaminerController`/`ProjectExaminerController`)، وتحديث `ProjectController`, `ProjectProposalWebController`, `SearchService`, `ReportService`, `DashboardController`, `Admin/UserController`, `ProjectProposalPolicy`, إشعار `ProjectProposalStatusChanged` (المشرف لم يعد له حساب دخول — التوجيه للبريد فقط عبر `Notification::route('mail', ...)` بدل قناتي database/broadcast)، `ProjectsImport` (البحث عن المشرف بالبريد ضمن `faculty_members` بدل التحقق من دور `supervisor`)
- **Routes:** `/faculty-members`، `/academic-degrees` (super_admin فقط)، إزالة `supervisor` من كل مجموعات الصلاحيات في `routes/web.php`
- **الواجهات:** صفحتا `FacultyMembers/Index.vue` و`AcademicDegrees/Index.vue` (جديدتان، بديل `Examiners/Index.vue`)، `AssignFacultyMemberModal.vue` (بديل `AssignExaminerModal.vue`)، وتحديث كل الصفحات التي تعرض المشرف/الممتحنين: `Projects/Show|Create|Edit.vue`, `ArchiveProjectModal.vue`, `ProposalForm.vue`, `Proposals/*.vue`, `Reports/*.vue`, `Search/Index.vue`, `Public/Browse|Show.vue`, `Admin/Users/Index.vue`, `Dashboard.vue`, `AppSidebar.vue` (رابط "أعضاء هيئة التدريس" بدل "الممتحنون" + رابط جديد "الدرجات العلمية" لـ super_admin فقط)
- **Seeders/Factories:** `RoleSeeder` (3 أدوار)، `AcademicDegreeSeeder` (جديد، يزرع دبلوم/بكالوريوس/ماجستير/دكتوراه)، `FacultyMemberFactory` (بديل `ExaminerFactory`)، `AcademicDegreeFactory` (جديد)، إعادة كتابة `DummyDataSeeder` بالكامل لاستخدام `FacultyMember` بدل مستخدمي `supervisor`
- **الاختبارات:** `AcademicDegreeTest.php` و`FacultyMemberTest.php` (جديدان، بديل `ExaminerTest.php`)، وتحديث `RoleVerificationTest` (حذف كتلتي اختبار `supervisor`/`viewer` بالكامل)، `RBACTest`، `UserManagementTest`، `RoleSeederTest`، `DepartmentTest`، `ProjectTest`، `ProjectModelTest`، `UserModelTest` (+ `FacultyMemberModelTest.php` جديد)، `ProjectProposalTest`، `ReportTest`، `ImportTest`، `PublicBrowseTest`، `SearchTest`
- **التحقق:** `php artisan migrate:fresh --seed` نجح على قاعدة MariaDB الفعلية. `php artisan test` → **208/216 ناجحة**؛ الاختبارات الثمانية المتبقية **سابقة على هذا التعديل ولا علاقة لها به** (فحص فعلي: `Database\Factories\ProjectProposalFactory` غير موجود أصلاً في المشروع — 6 اختبارات فشل بسببه؛ `ProjectProposalApiTest` يُنشئ أدواراً بأسماء مختلفة تماماً بشرطة `super-admin` غير متصلة بنظام الصلاحيات الفعلي؛ اختبار في `RBACTest` يناقض إعداد الـ route الفعلي المسموح فيه لـ`dept_manager`). فُحصت الصفحات المصادَق عليها فعلياً عبر جلسة curl (تسجيل دخول admin@admin.com) على كل من: `/dashboard`, `/faculty-members`, `/academic-degrees`, `/departments`, `/projects`, `/projects/{id}`, `/projects/{id}/edit`, `/projects/create`, `/projects/archived`, `/reports/department`, `/reports/supervisors`, `/search`, `/browse`, `/admin/users` — كلها 200 بدون أخطاء SQL.
- **قرارات محفوظة من نقاش سابق مع المستخدم:** (1) عضو هيئة التدريس يتبع أكثر من قسم (M:N)، (2) إعادة تسمية داخلية كاملة (جداول/أعمدة/routes) لأن البيانات تجريبية بالكامل، (3) زرع درجات علمية افتراضية عند أول تشغيل.
- **ملاحظة غير منفَّذة:** لم يُحدَّث `PROGRESS.md` (ملف توثيق تقني تفصيلي ضخم بصيغة جدول لكل جدول/نموذج/كنترولر) — تُرك على حاله بناءً على طلب المستخدم، ولا يزال يصف الحالة قبل هذا التعديل.

### ⚠️ ملاحظة غير منفَّذة — نص PDF العربي غير مفهوم (dompdf)
- لم تُطلب هذه النقطة ضمن هذه الدفعة (#4–#8). هذه مشكلة جوهرية في مكتبة dompdf v3.1.5 نفسها (لا تدعم تشكيل الحروف العربية/bidi reordering) — تحتاج قراراً منفصلاً بين معالجة النص العربي يدوياً قبل dompdf أو التحول لمحرك PDF مختلف (Browsershot/Chromium)، وتم شرح الخيارين للمستخدم مسبقاً دون تنفيذ.

### ✅ #11 إصلاح خطأ `system_settings doesn't exist` — ترحيل 13 migration معلّقة كانت معطوبة فعلياً
- **تاريخ التنفيذ:** 2026-08-15
- **المشكلة الأصلية:** الصفحة الرئيسية كانت تفشل بخطأ `SQLSTATE[42S02]: Base table or view not found: system_settings` لأن `HandleInertiaRequests` يستدعي `SystemSetting::current()` في كل طلب، والجدول لم يكن منشأً بعد. الفحص كشف أن 13 migration بتاريخ `2026_08_14` (نفس دفعة إعادة الهيكلة #10 أعلاه) كانت **معلّقة (Pending) ولم تُنفَّذ إطلاقاً** على قاعدة البيانات المحلية.
- **أعطاب حقيقية اكتُشفت أثناء التنفيذ (وليست فقط "لم تُشغَّل بعد"):**
  - `2026_08_14_000002` (`examiners`→`faculty_members`): عمود `degree_id` الجديد أُضيف `NOT NULL` بدون قيمة افتراضية على جدول به بيانات فعلية → فشل فوري بخطأ FK 1452. الإصلاح: `nullable()`.
  - `2026_08_14_000004` (`project_examiners`→`project_faculty_members`) و `2026_08_14_000005` (`evaluations.examiner_id`→`faculty_member_id`): كانتا تضيفان العمود الجديد `NOT NULL` (أو تحذفان العمود القديم قبل نقل قيمته) بدل نقل البيانات أولاً. الإصلاح: إضافة العمود كـ `nullable`، تنفيذ `UPDATE ... SET new = old`، ثم `->nullable(false)->change()`، ثم حذف العمود القديم.
  - `2026_08_14_000006`/`000007` (`supervisor_id` على `projects`/`project_proposals`): كانتا تحذفان العمود القديم (مرتبط بـ `users`) وتنشئانه من جديد مرتبطاً بـ `faculty_members` بدون نقل أي بيانات — فقدان كامل لروابط الإشراف. الإصلاح: `nullable()` (القيم التجريبية أُعيد توليدها لاحقاً بالسيدر).
  - في 3 حالات ظهر خطأ إضافي `Can't DROP FOREIGN KEY ...; check that it exists` — تبيّن أن القيود الفعلية في قاعدة البيانات لا تحمل دائماً الاسم الذي يتوقعه Laravel تلقائياً. أُضيفت دالة مساعدة `hasForeignKey()` في كل migration متأثر تتحقق من `information_schema.KEY_COLUMN_USAGE` قبل استدعاء `dropForeign()`.
  - **مشكلة توافق إضافية اكتُشفت لاحقاً (أثناء تشغيل الاختبارات في نفس اليوم):** استعلام `information_schema.KEY_COLUMN_USAGE` خاص بـ MySQL فقط ولا يعمل على SQLite (قاعدة بيانات الاختبارات في الذاكرة) → كان يُسقط 24 اختباراً بخطأ `no such table: information_schema.KEY_COLUMN_USAGE`. أُصلحت دالة `hasForeignKey()` في الملفات الثلاثة لتتحقق من `DB::connection()->getDriverName()` أولاً وتُرجع `true` مباشرة (تجرِّب الحذف) على أي محرك غير MySQL.
- **الملفات المتأثرة:** `database/migrations/2026_08_14_000002_*.php`، `2026_08_14_000004_*.php`، `2026_08_14_000005_*.php`، `2026_08_14_000006_*.php`، `2026_08_14_000007_*.php`
- **إعادة بناء البيانات:** بعد إصلاح الـ migrations، نُفِّذ `php artisan migrate:fresh --seed` (بموافقة صريحة من المستخدم) لأن بيانات `evaluations` و`supervisor_id` التجريبية كانت قد فُقد ربطها أثناء محاولات الترحيل الفاشلة الأولى قبل اكتشاف الحل الصحيح.
- **التحقق:** كل الـ 13 migration تُنفَّذ الآن بنجاح من الصفر على MariaDB الفعلية دون أي خطأ. `php artisan test` (على SQLite) → لا مزيد من أخطاء `information_schema` بعد الإصلاح.

### ✅ #12 إعداد نظام جديد: الحد الأقصى لعدد المشاريع للمشرف في الفصل الدراسي الواحد
- **تاريخ التنفيذ:** 2026-08-15
- **الشرح:** أُضيف إعداد `max_projects_per_supervisor_per_semester` (افتراضي 5) إلى شاشة إعدادات النظام، يحدّد أقصى عدد مقترحات/مشاريع يمكن لعضو هيئة تدريس واحد الإشراف عليها ضمن نفس `academic_year` + `semester`. القرار (بعد سؤال المستخدم): التحقق يُطبَّق عند **إنشاء أو تعديل المقترح** (وليس لاحقاً في دورة حياة المشروع)، لأن كل مشروع فعلي ينشأ أصلاً من مقترح مُعتمَد.
- **قاعدة البيانات:** migration جديدة `2026_08_15_135332_add_max_projects_per_supervisor_per_semester_to_system_settings_table.php` — عمود `unsignedInteger` افتراضي 5، بعد `examiners_per_project`.
- **Backend:**
  - `app/Models/SystemSetting.php` — العمود الجديد أُضيف لـ `$fillable`
  - `database/seeders/SystemSettingSeeder.php` — قيمة افتراضية 5
  - `app/Http/Requests/UpdateSystemSettingRequest.php` — قاعدة تحقق `required|integer|min:1|max:50`
  - `app/Rules/SupervisorSemesterCapacity.php` (جديد) — قاعدة تحقق مخصصة (`ValidationRule`): تحسب عدد مقترحات المشرف المُعطى في نفس `academic_year`+`semester` بحالة **غير** `rejected`/`superseded` (هذه لا تمثل عبء إشراف فعلي)، وتفشل إن كان العدد ≥ الحد الأقصى المُعرَّف في `SystemSetting`. تدعم استثناء مقترح معيّن من العدّ (`$excludeProposalId`) عند التعديل حتى لا يُحتسب المقترح ضد نفسه.
  - `app/Http/Requests/StoreProjectProposalRequest.php` / `UpdateProjectProposalRequest.php` — القاعدة الجديدة أُضيفت إلى مصفوفة تحقق `supervisor_id`؛ في التعديل يُستخرج `proposal_id` من الـ route (مع دعم الحالتين: نموذج `ProjectProposal` محلول مسبقاً أو ID خام) لتمرير `excludeProposalId`.
  - `app/Http/Middleware/HandleInertiaRequests.php` — العمود الجديد أُضيف إلى مفتاح `systemSettings` المشترك عالمياً عبر Inertia.
- **Frontend:** `resources/js/pages/Admin/SystemSettings.vue` — حقل رقمي جديد ضمن بطاقة "إعدادات المشاريع" مرتبط بـ `form.max_projects_per_supervisor_per_semester`. `resources/js/types/index.ts` — الحقل أُضيف لواجهة `SystemSettings` المشتركة.
- **الاختبارات:** `tests/Feature/Proposal/ProposalTest.php` — 4 اختبارات جديدة: تجاوز الحد يُرفض، الحالات المرفوضة/المُستبدَلة لا تُحتسب، الحد يُحسب لكل فصل دراسي على حدة (مقترح في فصل آخر لا يُحتسب)، التعديل يستثني المقترح نفسه من عدّه. `tests/Feature/Settings/SystemSettingTest.php` — تحديث اختبار "can update system settings" الموجود ليمرر الحقل الجديد المطلوب.
- **التحقق:** `php artisan test` → **232/233 ناجحة**. الفشل الوحيد المتبقي (`RBACTest > dept_manager cannot access admin panel`) **سابق على هذا التعديل بالكامل** — تم التأكد بـ `git stash` والتشغيل على الكود الأصلي قبل أي تعديل من هذه الجلسة، وكان يفشل بنفس الطريقة تماماً.

### ✅ #13 منصة دورة حياة مشروع التخرج — Phase 1: استيراد كشف الطلاب وإنشاء حساباتهم
- **تاريخ التنفيذ:** 2026-08-15
- **السياق:** أول مرحلة من خطة أكبر مكوّنة من 8 مراحل (موثّقة بالكامل كبند `#11` في `plans_to_execute.md`) لتحويل النظام من أرشفة مشاريع إلى منصة متكاملة لدورة حياة التخرج (استيراد طلاب، بوابة طالب، بوابة مشرف، إشعارات، أفكار مشاريع). الفجوة الجوهرية المكتشفة أثناء الفحص: النظام لم يكن يملك أي مفهوم "حساب طالب يسجّل دخول" — `ProjectStudent` كان بيانات وصفية فقط بدون حساب. هذه المرحلة تُنشئ حسابات دخول طلاب حقيقية للمرة الأولى.
- **قرارات معمارية محسومة مع المستخدم قبل التنفيذ (بعد نقاش عبر عدة أسئلة توضيحية):**
  - جدول `students` جديد **مستقل تماماً** عن `project_students` الموجود (الذي يبقى بلا أي تغيير، بيانات أرشيفية وصفية للمشاريع المكتملة فقط).
  - اسم الدخول = **رقم القيد** مباشرة (وليس بريداً حقيقياً). آلياً: بريد داخلي تركيبي غير حقيقي `{registration_number}@students.local` يُخزَّن في `users.email` كمعرّف تقني فقط، والطالب لا يراه.
  - كلمة المرور المؤقتة = **تاريخ الميلاد** بصيغة `DDMMYYYY` — **اختيار المستخدم صراحةً** بعد تحذير أمني واضح منّي (تاريخ الميلاد بيانات شبه-عامة قابلة للتخمين ضمن نطاق ضيق). عُوِّض هذا القرار بإجبار تغيير كلمة المرور عند أول دخول (`force_password_change`) كحد أدنى من الحماية.
  - آلية الدخول برقم القيد تطلّبت لمس ملف مصادقة مشترك (`LoginRequest`) يستخدمه كل الأدوار الحالية — تم تنفيذها كإضافة صرفة (branch جديد فقط عند عدم وجود `@` في المدخل) بدون أي تغيير في مسار البريد الإلكتروني الحالي، ومختبرة صراحةً لضمان عدم انكسار دخول الموظفين.
  - `/projects` و `/search` تُركا مفتوحتين لأي مستخدم مسجّل دخول (بما فيهم `student` الآن) بقرار صريح من المستخدم — سيُعالَج التقييد المناسب في Phase 2 عند بناء بوابة الطالب الفعلية.
- **قاعدة البيانات (5 migrations جديدة):**
  - `system_settings.registration_number_length` (افتراضي 9) — لشرط "التحقق من صحة رقم القيد وفق طول رقم القيد الموجود في إعدادات النظام".
  - `users.force_password_change` (boolean، افتراضي false) — لا يؤثر على أي مستخدم حالي.
  - `students` (جديد): `full_name`, `national_id` (unique، 12 رقم)، `registration_number` (unique)، `department_id`/`specialization_id` (FK، `restrictOnDelete`)، `semester`, `academic_year`, `date_of_birth`, `user_id` (nullable، FK→users، unique).
  - `imported_student_batches` (جديد): سجل تعريفي لكل عملية استيراد (`imported_by`, `total_rows`, `success_count`, `failed_count`).
  - `imported_student_rows` (جديد): سجل تدقيق كامل لكل صف في كل عملية استيراد (ناجح وفاشل معاً)، بحالة مصنَّفة (`success`/`duplicate_in_file`/`already_exists`/`invalid_national_id`/`invalid_registration_number`/`invalid_data`) وسبب الخطأ.
  - حراسة حذف إضافية: `DepartmentController::destroy()` و`SpecializationController::destroy()` يمنعان الحذف الآن إذا وُجد طلاب مرتبطون (بنفس نمط حراسة المشاريع الموجودة مسبقاً).
- **Backend:**
  - `app/Models/Student.php`, `ImportedStudentBatch.php`, `ImportedStudentRow.php` (جديدة) + تحديث `User` (علاقة `student()`, دعم `force_password_change`)، `Department`/`Specialization` (علاقة `students()`).
  - `app/Imports/StudentsImport.php` (جديد، بنمط `ToCollection`/`WithHeadingRow` مطابق لـ `ProjectsImport` الموجود) — يطبّق الشروط العشرة المطلوبة: تحقق الأعمدة المطلوبة، الرقم الوطني 12 رقماً بالضبط، طول رقم القيد حسب إعداد النظام، منع التكرار **داخل نفس الملف** وداخل قاعدة البيانات معاً، اكتشاف الطلاب الموجودين مسبقاً (بدون إنشاء حساب مكرر)، Preview كامل قبل الاعتماد (`dryRun`) بدون أي كتابة لقاعدة البيانات، عزل فشل كل صف عن البقية (نمط "per-row isolation" المستخدم في `ProjectsImport`). عند الاستيراد الفعلي: كل صف ناجح يُنشئ `Student` + `User` (دور `student`، بريد تركيبي، كلمة مرور من تاريخ الميلاد، `force_password_change=true`) داخل معاملة واحدة (`DB::transaction`)، ويُرسِل إشعار `StudentAccountCreated` (قناة `database` فقط — لا بريد حقيقي للطالب).
  - `app/Exports/StudentImportTemplate.php` (جديد) — قالب Excel بنفس تنسيق `ProjectImportTemplate` (رأس أزرق، صف مثال أصفر).
  - `app/Http/Controllers/StudentImportController.php` (جديد) — `index`/`downloadTemplate`/`preview`/`import`، بنفس نمط `ImportController` الموجود تماماً.
  - `app/Http/Controllers/StudentController.php` (جديد) — `index` (جدول مع فلاتر بحث/تخصص/فصل/سنة/حالة حساب، مع نطاق تلقائي لقسم `dept_manager`)، `show`، `toggleActive`، `resetPassword` (يولّد كلمة مرور عشوائية آمنة جديدة عبر `Str::password()`، يعرضها مرة واحدة فقط للمسؤول عبر flash، ويُعيد `force_password_change=true`).
  - `app/Http/Middleware/EnsurePasswordIsChanged.php` (جديد، مُسجَّل عالمياً في `bootstrap/app.php` ضمن مجموعة `web`) — يحوّل أي مستخدم `force_password_change=true` إلى صفحة `settings/password` الموجودة أصلاً (لم تُبنَ صفحة جديدة) قبل الوصول لأي شيء آخر، باستثناء صفحة التغيير نفسها و`logout`. المستخدمون الحاليون (`force_password_change=false` افتراضياً) غير متأثرين إطلاقاً.
  - `app/Http/Controllers/Settings/PasswordController.php` — تعديل سطر واحد: `update()` يمسح `force_password_change` عند نجاح تغيير كلمة المرور (يعمل لأي مستخدم، ليس الطالب فقط).
  - `app/Http/Requests/Auth/LoginRequest.php` — إضافة `resolveLoginEmail()`: أي مدخل يحتوي `@` يمر كما هو (بريد حقيقي، الأدوار الحالية)؛ أي مدخل بدون `@` يُعامَل كرقم قيد ويُبحث عن `Student` المطابق للحصول على بريد حسابه المرتبط. قاعدة التحقق `email` الصارمة على الحقل حُذفت لصالح `string` عام (لا تغيير على منطق `Auth::attempt` نفسه).
  - `database/seeders/RoleSeeder.php` — دور `student` أُضيف (4 أدوار الآن)، ونُفِّذ يدوياً على قاعدة MariaDB الفعلية.
- **Frontend:**
  - `resources/js/pages/Students/Import.vue` (جديد) — معالج 3 خطوات (تحميل قالب → رفع ومعاينة → تأكيد)، بنفس تصميم `Import/Index.vue` الموجود (بطاقات إحصاء، جدول معاينة، عرض حالة كل صف).
  - `resources/js/pages/Students/Index.vue` (جديد) — جدول الطلاب المستوردين بكل الأعمدة المطلوبة (الاسم، رقم القيد، الرقم الوطني **مخفي جزئياً** عبر `Student::maskedNationalId()`، القسم، التخصص، الفصل/السنة، حالة الحساب، حالة المشروع [عرض ثابت "لم يبدأ بعد" — لا ربط فعلي بعد لعدم وجود نظام أفكار/مقترحات مربوط بالطالب حتى Phase 5+]، تاريخ إنشاء الحساب)، مع إجراءات [عرض]/[تفعيل-تعطيل]/[إعادة تعيين كلمة المرور] وModal يعرض كلمة المرور الجديدة مرة واحدة فقط.
  - `resources/js/pages/Students/Show.vue` (جديد) — صفحة تفاصيل طالب واحد.
  - `resources/js/pages/auth/Login.vue` — تعديل حقل البريد من `type="email"` صارم إلى `type="text"` مع تسمية "البريد الإلكتروني أو رقم القيد" (كي لا يمنع متصفح المستخدم إدخال رقم قيد عبر تحقق HTML5 المدمج).
  - `resources/js/components/AppSidebar.vue` — رابطا "الطلاب" و"استيراد الطلاب" أُضيفا لقائمتي `super_admin` و`dept_manager`.
  - `resources/js/pages/Admin/SystemSettings.vue` + `app/Http/Requests/UpdateSystemSettingRequest.php` — حقل "طول رقم القيد" جديد ضمن بطاقة إعدادات المشاريع.
- **الاختبارات (32 اختباراً جديداً، كلها ناجحة):**
  - `tests/Feature/Student/StudentImportTest.php` (17 اختباراً) — صلاحيات الوصول، تحميل القالب، رفض ملفات غير Excel، إنشاء حساب فعلي كامل (بريد/دور/كلمة مرور/إجبار تغيير/إشعار)، تدقيق `imported_student_batches`/`imported_student_rows`، تكرار الرقم الوطني/رقم القيد **داخل نفس الملف**، اكتشاف طالب موجود مسبقاً، تحقق طول الرقم الوطني (12) ورقم القيد (حسب الإعداد)، حقول ناقصة، قسم/تخصص/فصل غير معروف، Preview لا يكتب أي شيء لقاعدة البيانات، تجميع نتائج مختلطة.
  - `tests/Feature/Student/StudentManagementTest.php` (7 اختبارات) — صلاحيات القائمة (super_admin/dept_staff/dept_manager)، نطاق `dept_manager` على قسمه فقط، تفعيل/تعطيل الحساب، إعادة تعيين كلمة المرور وعرضها مرة واحدة، فشل آمن عند عدم وجود حساب.
  - `tests/Feature/Student/StudentLoginTest.php` (8 اختبارات) — تسجيل دخول الموظف بالبريد يعمل كما كان (اختبار ارتداد صريح)، دخول الطالب برقم القيد يعمل، رقم قيد غير موجود يفشل بأمان (بدون تسريب معلومة)، كلمة مرور خاطئة تفشل، التحويل الإجباري لصفحة تغيير كلمة المرور، صفحة التغيير نفسها تبقى متاحة أثناء الإجبار، تغيير كلمة المرور يمسح الإجبار ويفتح التنقّل الطبيعي، مستخدم عادي بلا إجبار لا يتأثر إطلاقاً.
  - تحديث اختبارين موجودين مسبقاً (نتيجة متوقعة ومباشرة لهذه المرحلة، وليست أعطالاً): `RoleSeederTest` (3 أدوار → 4)، `SystemSettingTest > super_admin can update system settings` (إضافة الحقل الجديد المطلوب `registration_number_length`).
- **التحقق:** `php artisan test` → **264/265 ناجحة**. الفشل الوحيد المتبقي (`RBACTest > dept_manager cannot access admin panel`) **سابق على كل عمل هذه الجلسة** (مؤكَّد سابقاً في البند #12). `npm run build` نجح بدون أخطاء (كل صفحات Vue الجديدة تُصرَّف بنجاح). الميغريشنز الخمس نُفِّذت بنجاح على MariaDB الفعلية، ودور `student` + `registration_number_length=9` مُتحقَّق منهما مباشرة على قاعدة البيانات الحية.
- **ملاحظة `vue-tsc`:** فشل `npx vue-tsc --noEmit` بخطأ `TS2688: Cannot find type definition file` — تبيّن أنه عطل بيئة/إعداد أدوات **سابق على هذا التعديل** وغير متعلق بالكود (فشل في العثور على ملفات النوع الخاصة بالمشروع نفسه قبل فحص أي كود)؛ استُخدم `npm run build` (Vite/esbuild) كبديل تحقّق ناجح.
- **ما لم يُنفَّذ بعد (مؤجَّل عمداً لمراحل لاحقة موثّقة في `plans_to_execute.md#11`):** بوابة الطالب الفعلية (`/student` العامة + `/student/dashboard` الخاصة)، تقييد `/projects`/`/search` عن دور `student`، بوابة المشرف، نظام الإشعارات المُعمَّم، أفكار المشاريع، وكل ما بعدها من Phase 2 إلى Phase 8.

### ✅ #14 منصة دورة حياة مشروع التخرج — Phase 2: بوابة الطالب
- **تاريخ التنفيذ:** 2026-08-16
- **السياق:** المرحلة الثانية من خطة الـ8 مراحل (`plans_to_execute.md#11`). تبني على Phase 1 (حسابات الطلاب) لتفعيل بوابة طالب فعلية: تقديم/تعديل مقترح المشروع، متابعة دورة الحياة بصرياً (17 مرحلة)، رؤية بيانات المشرف والملاحظات.
- **قرارات معمارية محسومة مع المستخدم قبل التنفيذ:**
  - **ربط الفريق:** `project_proposal_students.student_id` (nullable FK→students) يُحلّ تلقائياً بمطابقة `registration_number` عند إنشاء/تعديل أي مقترح (لا حاجة لربط يدوي). كل طالب مرتبط بنفس الفريق (عبر `student_id`) يرى نفس المقترح المشترك — **رؤية جماعية كاملة، وليست حصرية على منشئ المقترح فقط**.
  - **ملاحظات المشرف/القسم:** حقلان جديدان `supervisor_note`/`department_note` على `project_proposals` (منفصلان عن `rejection_reason` الموجود). **دون انتظار بوابة مشرف منفصلة (Phase 3)** — يملأ `dept_manager` كليهما في نفس إجراء المراجعة الحالي (تعليمات المستخدم صراحة: "اجعل المشرف بنفس العمل الحالي لا يحتاج إلى بوابة منفصلة").
  - **إصدارات الملفات:** أُبقي السلوك الحالي كما هو بقرار صريح من المستخدم (الملف القديم يُستبدل عند رفع ملف جديد، لا تاريخ إصدارات) — **يُخالف** الطلب الأصلي "الاحتفاظ بجميع الإصدارات"، لكنه تفضيل واعٍ للمستخدم بعد عرض الخيارين، ومُسجَّل هنا للشفافية.
  - **دورة الحياة (17 مرحلة):** جدول `lifecycle_stages` ثابت (seed فقط) **بدون** جدول تتبع تقدّم منفصل — المرحلة الحالية تُحسب **تلقائياً وحيّاً** من الحالات النظامية الموجودة (`project_proposals.status`, `project.current_status_id` عبر `sort_order` الموجود في `project_status`) في كل طلب، دون أي تخزين إضافي يحتاج مزامنة. مراحل بلا مقابل نظامي حالي (مراجعة المشرف الفعلية، موافقة المشرف على التوثيق، التدريب الميداني، إخلاء الطرف، اكتمال إجراءات التخرج) تبقى "قادمة" حتى تُبنى الأنظمة المقابلة لها في مراحل لاحقة — قرار مقصود ومقبول من المستخدم.
- **قاعدة البيانات (3 migrations جديدة):**
  - `project_proposal_students.student_id` (nullable، FK→students، `nullOnDelete`).
  - `project_proposals.supervisor_note` + `department_note` (نصّان، nullable).
  - `lifecycle_stages` (جديد): `key` (unique)، `name_ar`، `sort_order`، `requires_student_action`. مزروع بـ17 صفاً عبر `LifecycleStageSeeder` (مُضاف إلى `DatabaseSeeder`).
- **Backend:**
  - `app/Models/LifecycleStage.php` (جديد). تحديث `Student` (علاقة `proposalMemberships()`, دالتا `currentProposal()` و`hasActiveProposal()`)، `ProjectProposalStudent` (علاقة `student()`)، `ProjectProposal` (الحقلان الجديدان في `$fillable`).
  - `app/Services/LifecycleTimelineService.php` (جديد) — يحسب حالة كل من الـ17 مرحلة (`completed`/`current`/`upcoming`) ووسم `needs_student_action` بمقارنة مباشرة مع الحالات الفعلية، بدون أي تخزين وسيط.
  - `app/Repositories/ProjectProposalRepository.php` — `create()`/`update()` يحلّان `student_id` تلقائياً لكل صف طالب مُدخَل عبر مطابقة `registration_number`؛ `find()` وسّع الـ eager-loading لـ `project.currentStatus`/`project.documents` (متوافق خلفياً 100% — الواجهة القديمة تستخدم `.id` فقط من `project`).
  - `app/Policies/ProjectProposalPolicy.php` — فروع جديدة لدور `student`: `viewAny`/`view` (عضوية الفريق فقط عبر `student_id`)، `create` (يُمنع إن كان لديه مقترح نشط بالفعل — `pending`/`needs_revision`)، `update` (عضوية الفريق + الحالة `pending`/`needs_revision` فقط)، `delete`/`changeStatus` (ممنوعان دائماً للطالب). لا تغيير على صلاحيات الأدوار الثلاثة الحالية.
  - `app/Http/Requests/Concerns/EnsuresStudentTeamMembership.php` (trait جديد، يُستخدم في `StoreProjectProposalRequest`/`UpdateProjectProposalRequest`) — يُدرج بيانات الطالب المسجّل دخوله في مصفوفة `students` ضمن `prepareForValidation()` (**قبل** التحقق، وليس بعده في الكونترولر) — ضروري لأن قاعدة `students: required|min:1` كانت سترفض الطلب قبل وصوله لمنطق الكونترولر لو تُرك الحقل فارغاً من الواجهة.
  - `app/Http/Requests/ChangeProposalStatusRequest.php` + `ProjectProposalService::changeStatus()` + `ProjectProposalWebController::changeStatus()` — دعم `supervisor_note`/`department_note` اختياريين مع أي إجراء (رفض/طلب تعديل/اعتماد)، وليس فقط سبب الرفض.
  - Namespace جديد `app/Http/Controllers/Student/` (منفصل عن `App\Http\Controllers\StudentController` الخاص بإدارة الطاقم من Phase 1، تجنباً لتعارض التسمية): `DashboardController` (بيانات الطالب + مقترحه الحالي + الـtimeline)، `ProposalController` (create/store/show/edit/update، محصورة بفريق الطالب فقط عبر الـPolicy، تُعيد استخدام `ProjectProposalService`/`ProjectProposalRepository` الموجودين بالكامل).
  - `app/Http/Controllers/PublicController.php` — `browse()` أُعيد بناؤه داخلياً حول دالة خاصة مشتركة `renderBrowse()`؛ دالة جديدة `studentPortal()` تستدعيها بنفس منطق الاستعلام (بدون أي تكرار كود) وتُصيّر **نفس مكوّن** `Public/Browse.vue` براوت مختلف.
  - `app/Http/Controllers/ReportController.php::dashboard()` — سطر إضافي فقط: مستخدم بدور `student` يُوجَّه فوراً لـ `/student/dashboard` (لا تأثير على أي دور آخر).
  - Routes جديدة: `GET /student` (عامة، بدون تسجيل دخول) + مجموعة `auth+role:student` تحت بادئة `/student` (`dashboard`, `proposal/create`, `proposal` POST, `proposal/{id}`, `proposal/{id}/edit`, `proposal/{id}` PUT).
- **Frontend:**
  - `resources/js/components/LifecycleTimeline.vue` (جديد) — مكوّن Timeline عمودي قابل لإعادة الاستخدام (مكتمل/حالي/قادم + وسم "يتطلب إجراءً منك").
  - `resources/js/pages/Public/Browse.vue` — إضافة `routeName`/`loginLabel` كخاصيتين اختياريتين (افتراضهما القيم الحالية تماماً — **لا تغيير في السلوك الافتراضي**) تُستخدمان في الفلترة/الترقيم ونص زر الدخول، لجعل نفس المكوّن قابلاً لإعادة الاستخدام في `/student` بزر "دخول طالب" بدل "تسجيل الدخول" دون تكرار أي كود.
  - `resources/js/components/Proposals/ProposalFormModal.vue` — إضافة خصائص اختيارية `storeRouteName`/`updateRouteName` (افتراضهما مسارات الطاقم الحالية — **لا تغيير** لاستخدامات الطاقم الموجودة)، و`defaultStudent`/`defaults` لتعبئة الصف الأول ببيانات الطالب المسجّل دخوله وقيم افتراضية عند إنشاء مقترح من بوابة الطالب.
  - `resources/js/pages/Student/Dashboard.vue`, `Student/Proposal/{Create,Edit,Show}.vue` (جديدة بالكامل) — تُعيد استخدام `ProposalFormModal` و`proposalStatusBadge.ts` و`LifecycleTimeline.vue` الموجودة/الجديدة، بدون أي نمط تصميم مغاير.
  - `resources/js/pages/Proposals/Show.vue` (شاشة الطاقم الحالية) — إضافة عرض/تحرير `supervisor_note`/`department_note` (حقلا نص ضمن "اتخاذ إجراء" + عرض دائم إن وُجدا)، متوافق خلفياً بالكامل مع الحقول السابقة.
  - `resources/js/components/AppSidebar.vue` — قسم `student` جديد في `navByRole` (رابطا "لوحة التحكم" → `/student/dashboard` و"تصفح المشاريع").
- **الاختبارات (16 اختباراً جديداً، + تحديث اختبار قديم واحد أصبح غير دقيق):**
  - `tests/Feature/Student/StudentProposalTest.php` (12 اختباراً) — تقديم مقترح مع إدراج تلقائي للطالب في الفريق، منع مقترح نشط ثانٍ، رؤية مشتركة للفريق (زميل غير المُنشئ يرى نفس المقترح)، منع طالب خارج الفريق من الرؤية، السماح بالتعديل أثناء `pending`/`needs_revision` فقط، منع تغيير الحالة من الطالب، حالة اللوحة الفارغة بلا مقترح، تحويل `/dashboard` العام لطالب إلى `/student/dashboard`، صحة حساب مرحلتي Timeline في حالتين مختلفتين (مقترح جديد معلّق، ومقترح مُعتمَد مع مشروع)، وتسجيل ملاحظتي المشرف/القسم من `dept_manager` عبر نفس إجراء المراجعة.
  - `tests/Feature/Student/StudentPortalPageTest.php` (2 اختباران) — `/student` متاحة بدون تسجيل دخول وتعرض نفس بيانات `/browse` براوت/تسمية مختلفين، و`/browse` الأصلية غير متأثرة إطلاقاً.
  - تحديث `tests/Feature/Student/StudentLoginTest.php` (اختبار موجود من Phase 1، `changing the password clears the forced flag...`) — كان يفترض أن `/dashboard` يُرجع 200 مباشرة لطالب بعد تغيير كلمة المرور؛ أصبح الآن يتحقق من التحويل الصحيح لـ`/student/dashboard` (نتيجة متوقعة ومباشرة لإضافة هذه المرحلة، وليست عطلاً).
- **التحقق:** `php artisan test` → **278/279 ناجحة**. الفشل الوحيد المتبقي (`RBACTest > dept_manager cannot access admin panel`) **سابق على كل عمل هذا المشروع** (مؤكَّد مسبقاً عدة مرات). أثناء التشغيل ظهر فشل إضافي مؤقت في `StudentLoginTest` بسبب تحديث `/dashboard` ليُحوِّل الطالب لصفحته الخاصة — تم إصلاحه بتحديث توقّع الاختبار (موثّق أعلاه)، وليس عطلاً حقيقياً. `npm run build` نجح بدون أخطاء. الميغريشنز الثلاث ودور الـ17 مرحلة مُتحقَّق منها مباشرة على قاعدة MariaDB الفعلية.
- **ما لم يُنفَّذ بعد (مؤجَّل لمراحل لاحقة):** بوابة المشرف الفعلية (Phase 3)، نظام الإشعارات المُعمَّم لكل الأحداث المذكورة في الخطة الأصلية (Phase 4)، أفكار المشاريع وطلبات الانضمام (Phase 5/6)، Versioning حقيقي لملفات المقترح وحجز الأفكار (Phase 7 — علماً أن قرار عدم بناء نظام إصدارات ملفات الآن اتُّخذ صراحة من المستخدم في هذه المرحلة)، كشف التشابه الموسّع (Phase 8)، وتقييد `/projects`/`/search` عن دور `student` (لا يزال مؤجَّلاً بقرار المستخدم من Phase 1).

### ✅ #15 منصة دورة حياة مشروع التخرج — Phase 3: بوابة المشرف
- **تاريخ التنفيذ:** 2026-08-16
- **السياق:** المرحلة الثالثة من خطة الـ8 مراحل (`plans_to_execute.md#11`). تفعيل حساب دخول حقيقي لعضو هيئة التدريس (`FacultyMember`) لأول مرة، مع Dashboard وصفحة "مشاريعي" وTimeline (يُعاد استخدام `LifecycleTimelineService` من Phase 2 كما هو دون أي تعديل).
- **قرارات معمارية محسومة مع المستخدم قبل التنفيذ:**
  - **إعادة نظر في قرار Phase 2:** في Phase 2 تقرَّر أن `dept_manager` هو من يملأ `supervisor_note` نيابة عن المشرف (لعدم وجود بوابة له آنذاك). الآن وبعد بناء البوابة فعلياً، **قرَّر المستخدم صراحةً** أن يستطيع المشرف كتابة/تعديل ملاحظته الخاصة بنفسه من داخل بوابته — قدرة مضبوطة وضيّقة (كتابة ملاحظة فقط)، **بدون** منحه صلاحية الاعتماد/الرفض/طلب التعديل التي تبقى حصراً لـ`dept_manager`. قدرة `dept_manager` القديمة على ملء `supervisor_note` ضمن نفس إجراء المراجعة **أُبقيت كما هي أيضاً** (كلا الطريقين يكتبان لنفس العمود، لا تعارض).
  - **تعريف "يحتاج إجراءً" في لوحة تحكم المشرف:** مقترحات بحالة `pending` مُسنَدة له و`supervisor_note` لا يزال فارغاً (أي لم يُبدِ رأيه بعد). مجرد إحصاء إعلامي — المشرف لا يملك صلاحية اعتماد/رفض المقترح نفسه.
- **قاعدة البيانات (migration واحدة):**
  - `faculty_members.user_id` (nullable، FK→users، unique، `nullOnDelete`) — بنفس نمط `students.user_id` من Phase 1 تماماً.
- **Backend:**
  - تحديث `User` (علاقة جديدة `facultyMember(): HasOne`) و`FacultyMember` (`user_id` في `$fillable` + علاقة `user(): BelongsTo`).
  - `app/Notifications/SupervisorAccountCreated.php` (جديد) — على عكس إشعار الطالب في Phase 1، يستخدم قناتي `mail`+`database` معاً لأن عضو هيئة التدريس يملك بريداً حقيقياً بالفعل (لا حاجة لبريد تركيبي).
  - `app/Http/Controllers/FacultyMemberController.php::createAccount()` (جديد) — اسم الدخول = البريد الحقيقي الموجود أصلاً في السجل، كلمة المرور المؤقتة = رقم الجوال المسجَّل (بقرار سابق محسوم)، `force_password_change=true`، دور `supervisor` يُسنَد تلقائياً. حراسات: منع إنشاء حساب ثانٍ لنفس العضو، ومنع التعارض إن كان البريد مُستخدَماً بالفعل من مستخدم آخر (بدل ترك القيد الفريد في قاعدة البيانات يرمي خطأ 500 خام).
  - `app/Policies/ProjectProposalPolicy.php` — فرع `supervisor` جديد لـ `view` (فقط إن كان `supervisor_id` للمقترح يطابق عضو هيئة التدريس المرتبط بحسابه)، وقدرة جديدة مخصَّصة `updateNote()` (كتابة `supervisor_note` فقط — أضيق من `changeStatus` المتبقية حصراً لـ`dept_manager`/`super_admin`).
  - `app/Http/Controllers/Supervisor/DashboardController.php` (جديد) — إحصاءات: إجمالي المشاريع، قيد التنفيذ، منجزة، جاهزة للمناقشة (من `current_status_id` بنفس تعريفات `ReportService` الموجودة)، تحتاج إجراء (تعريف أعلاه)، توثيقات تحتاج مراجعة (`project_documents` حيث `approved_at IS NULL` على مشاريعه — إحصاء إعلامي فقط، لم تُبنَ واجهة اعتماد توثيق بعد)، تكليفات كممتحن (`project_faculty_members` الموجود من مرحلة سابقة).
  - `app/Http/Controllers/Supervisor/ProposalController.php` (جديد) — `index()` "مشاريعي" (كل المقترحات المُسنَدة له تاريخياً بأي حالة، كل صف معه المرحلة الحالية محسوبة عبر `LifecycleTimelineService` المُعاد استخدامه من Phase 2 حرفياً)، `show()` (تفاصيل + Timeline كامل)، `updateNote()` (حفظ `supervisor_note`).
  - `app/Http/Controllers/ReportController.php::dashboard()` — سطر إضافي: مستخدم بدور `supervisor` يُوجَّه لـ `/supervisor/dashboard` (بنفس نمط توجيه الطالب في Phase 2، لا تأثير على أي دور آخر).
  - Routes جديدة: `POST /admin/faculty-members/{facultyMember}/create-account` (ضمن مجموعة `role:super_admin,dept_manager` الموجودة أصلاً لإدارة أعضاء هيئة التدريس)، ومجموعة `auth+role:supervisor` تحت بادئة `/supervisor` (`dashboard`, `proposals`, `proposals/{id}`, `proposals/{id}/note`).
  - **لا تعديل على `LoginRequest`** — المشرف يسجّل الدخول ببريده الحقيقي تماماً مثل بقية الموظفين، فآلية الدخول الحالية تعمل بدون أي تغيير (خلافاً لحالة الطالب في Phase 1 التي احتاجت لمس هذا الملف).
- **Frontend:**
  - `resources/js/pages/FacultyMembers/Index.vue` — عمود جديد "حساب الدخول" (شارة "لديه حساب"/"بدون حساب") + زر "إنشاء حساب دخول" (يظهر فقط لعضو بلا حساب) مع Modal تأكيد يوضّح آلية اسم الدخول/كلمة المرور المؤقتة قبل التنفيذ.
  - `resources/js/types/index.ts` — إضافة `user_id: number | null` لواجهة `FacultyMember` المشتركة.
  - `resources/js/pages/Supervisor/Dashboard.vue`, `Supervisor/Proposals/{Index,Show}.vue` (جديدة بالكامل) — تُعيد استخدام `LifecycleTimeline.vue` و`proposalStatusBadge.ts` الموجودين من Phase 2 بدون أي تكرار. صفحة `Show.vue` تعرض نموذج تحرير `supervisor_note` (مرئي فقط عبر `canEditNote` القادم من `Policy::updateNote`) وعرض `department_note` للقراءة فقط.
  - `resources/js/components/AppSidebar.vue` — قسم `supervisor` جديد في `navByRole` (لوحة التحكم + مشاريعي).
- **الاختبارات (20 اختباراً جديداً، + تحديث اختبار قديم واحد):**
  - `tests/Feature/Supervisor/FacultyMemberAccountTest.php` (5 اختبارات) — إنشاء الحساب من `super_admin`/`dept_manager`، منع `dept_staff`، منع إنشاء حساب ثانٍ لنفس العضو، فشل آمن عند تعارض البريد مع مستخدم آخر، التحقق من الدور/كلمة المرور المؤقتة/`force_password_change`/الإشعار المُرسَل.
  - `tests/Feature/Supervisor/SupervisorPortalTest.php` (10 اختبارات) — الوصول للوحة التحكم، منع `dept_staff`، تحويل `/dashboard` العام لـ`/supervisor/dashboard`، صحة إحصاءات المشاريع والمقترحات المعلَّقة، استثناء مقترح له `supervisor_note` بالفعل من عدّاد "يحتاج إجراء"، نطاق "مشاريعي" (لا يرى مقترحات مشرف آخر)، منع رؤية/تعديل مقترح غير مُسنَد إليه، حفظ `supervisor_note` بنجاح، وتأكيد أن `dept_manager` يحتفظ بقدرته على ملء الملاحظة عبر إجراء المراجعة الموجود أصلاً (لا كسر لسلوك Phase 2).
  - تحديث `tests/Feature/Seeders/RoleSeederTest.php` (نتيجة متوقعة ومباشرة لإضافة دور `supervisor`، وليست عطلاً): 4 أدوار → 5.
- **التحقق:** `php artisan test` → **293/294 ناجحة**. الفشل الوحيد المتبقي (`RBACTest > dept_manager cannot access admin panel`) **سابق على كل عمل هذا المشروع** (مؤكَّد مسبقاً عدة مرات). أثناء التشغيل ظهر فشل إضافي مؤقت في `RoleSeederTest` (نتيجة متوقعة ومباشرة لإضافة دور `supervisor`) — تم إصلاحه بتحديث توقّع الاختبار (موثّق أعلاه)، وليس عطلاً حقيقياً. `npm run build` نجح بدون أخطاء. الميغريشن ودور `supervisor` مُتحقَّق منهما مباشرة على قاعدة MariaDB الفعلية.
- **ما لم يُنفَّذ بعد (مؤجَّل لمراحل لاحقة):** اعتماد التوثيق فعلياً من المشرف (الإحصاء موجود، لكن لا زر "اعتماد" بعد — لم يُطلب في نطاق هذه المرحلة)، نظام الإشعارات المُعمَّم (Phase 4)، أفكار المشاريع وطلبات الانضمام (Phase 5/6)، وبقية المراحل حتى Phase 8.

### ✅ #16 منصة دورة حياة مشروع التخرج — Phase 4: نظام الإشعارات
- **تاريخ التنفيذ:** 2026-08-16
- **السياق:** المرحلة الرابعة من خطة الـ8 مراحل (`plans_to_execute.md#11`). تحويل جرس الإشعارات — الذي كان مقصوراً فعلياً على `super_admin`/`dept_manager` ومربوطاً بميزة مختلفة تماماً (لوحة ملاحظات الزوار `/feedback`) — إلى مركز إشعارات شخصي حقيقي متاح لكل الأدوار الخمسة، بدون بناء أي بنية تحتية جديدة (يُعاد استخدام جدول `notifications` القياسي في Laravel + `Notifiable` trait الموجود على `User` بالفعل).
- **قرار النطاق المحسوم قبل التنفيذ:** المواصفة الأصلية عدَّدت نحو 17 حدثاً إشعارياً، لكن معظمها لا يملك آلية تفعيل حقيقية بعد (لا نظام جدولة مناقشة، لا نظام اعتماد توثيقات، لا نظام إخلاء طرف، لا نظام طلبات إشراف — هذه تنتمي لمراحل 5–8 لاحقة). تقرر الاقتصار في هذه المرحلة على 4 أحداث حقيقية قابلة للتفعيل فوراً من حالة النظام الحالية:
  1. تقديم مقترح جديد → مدير القسم المعني.
  2. إعادة تقديم مقترح بعد التعديل (`replaces_proposal_id`) → مدير القسم، برسالة مختلفة عن التقديم الجديد.
  3. تغيّر حالة المقترح (رفض/طلب تعديل/اعتماد) → **كل** أعضاء فريق الطلاب (وليس فقط منشئ المقترح كما كان سابقاً) + المشرف.
  4. تكليف عضو هيئة تدريس كممتحن → العضو نفسه (إن كان له حساب دخول).
  5. تحوّل المشروع لحالة "جاهز للمناقشة" (`current_status_id=6`) → المشرف.
- **توحيد غلاف الإشعارات:** كل إشعار الآن يحمل بنية موحّدة `title` (عنوان الإشعار نفسه) + `message` (نص واضح) + `url` (رابط قابل للنقر، يختلف حسب دور المستلم عبر دالة خاصة `urlFor()`). سابقاً كان `title` يُستخدم خطأً لعنوان المقترح نفسه بدل عنوان الإشعار، وبعض الإشعارات (`NewProjectFeedbackNotification`) كانت بلا `message` صريح.
- **Backend:**
  - `app/Notifications/ProposalSubmitted.php` (جديد) — يحمل علم `isResubmission` لتمييز رسالة "مقترح جديد" عن "إعادة تقديم بعد تعديل" بنفس الكلاس.
  - `app/Notifications/ExaminerAssigned.php`, `app/Notifications/ProjectReadyForDefense.php` (جديدان) — قناة `database` فقط، بنفس نمط الإشعارات الموجودة.
  - `app/Notifications/ProjectProposalStatusChanged.php` — أُعيدت كتابتها: دالة خاصة `urlFor()` تُرجع رابطاً مختلفاً حسب دور المستلم (`student.proposal.show` / `supervisor.proposals.show` / `proposals.show`)، تُستخدَم في كل من `toMail()` و`toDatabase()`؛ `toDatabase()` أصبح يحمل `title`/`message` حقيقيين بدل إساءة استخدام `title` لعنوان المقترح.
  - `app/Notifications/StudentAccountCreated.php`, `SupervisorAccountCreated.php` — أُضيف مفتاح `url` (رابط لوحة تحكم كل دور) لغلاف `toDatabase()` (لم يكن موجوداً سابقاً).
  - `app/Notifications/NewProjectFeedbackNotification.php` — أُضيف مفتاح `message` لاستكمال الغلاف الموحَّد (لا تغيير في السلوك، إضافة فقط).
  - `app/Services/ProjectProposalService.php` — دالة `notify()` الخاصة أُعيدت كتابتها لتشمل **كل** أعضاء الفريق (`$proposal->students->map(fn($s) => $s->student?->user)`) بدل منشئ المقترح فقط، مع تفضيل الإرسال لحساب المشرف الحقيقي إن وُجد (`$proposal->supervisor?->user`) والرجوع لمسار البريد المجهول (`Notification::route('mail', ...)`) فقط إن لم يكن له حساب؛ ودالة خاصة جديدة `notifyDepartment()` تُستدعى من `create()` لإشعار مديري قسم المقترح (`User::role('dept_manager')->where('department_id', ...)`) بحدث `ProposalSubmitted`.
  - `app/Http/Controllers/ProjectFacultyMemberController.php::assign()` — بعد الربط الناجح، إن كان لعضو هيئة التدريس المُكلَّف حساب دخول (`FacultyMember->user`) يُرسَل له `ExaminerAssigned`؛ لا إشعار إن لم يكن له حساب (لا خطأ، تجاهل صامت).
  - `app/Http/Controllers/ProjectController.php::update()` — إضافة ثابت `STATUS_READY_FOR_DEFENSE=6`؛ التقاط `current_status_id` قبل التحديث ومقارنته بالحالة الجديدة، وإرسال `ProjectReadyForDefense` للمشرف (إن كان له حساب) فقط عند **الانتقال الفعلي** لهذه الحالة لأول مرة (لا إشعار متكرر عند حفظ نفس الحالة).
  - `app/Http/Controllers/NotificationController.php` (جديد) — `index()` (صفحة كاملة مُرقَّمة لإشعارات المستخدم الحالي فقط)، `markAsRead($id)` (محصور بإشعارات المستخدم نفسه عبر `$request->user()->notifications()`، 404 إن كان الإشعار لمستخدم آخر)، `markAllAsRead()`.
  - Routes جديدة تحت `auth` فقط (بلا قيد دور — متاحة لكل الأدوار الخمسة): `GET/PATCH /notifications`, `/notifications/{id}/read`, `/notifications/read-all`.
  - `app/Http/Middleware/HandleInertiaRequests.php` — `notifications` المشتركة عالمياً أصبحت تحمل `recent` (آخر 6 إشعارات، بنفس الغلاف الموحَّد) بجانب `unreadCount` الموجود مسبقاً، لتغذية القائمة المنسدلة دون طلب إضافي.
- **Frontend:**
  - `resources/js/components/AppSidebarHeader.vue` — الجرس تحوَّل من رابط ثابت مقصور على `super_admin`/`dept_manager` نحو `/feedback` إلى قائمة منسدلة (`DropdownMenu`) حقيقية متاحة لكل الأدوار: آخر 6 إشعارات، نقطة زرقاء للإشعار غير المقروء، "تعليم الكل كمقروء"، رابط "عرض كل الإشعارات"، والنقر على أي إشعار يُعلِّمه كمقروء (إن لزم) وينتقل لرابطه.
  - `resources/js/pages/Notifications/Index.vue` (جديدة) — صفحة كاملة مُرقَّمة (نفس نمط تصفّح `Feedback/Index.vue` الموجود)، مع "تعليم الكل كمقروء".
  - `resources/js/components/AppSidebar.vue` — رابط "لوحة الملاحظات" (بدل "الإشعارات" سابقاً) بقي حصراً لـ`super_admin`/`dept_manager` ويشير كما كان إلى `/feedback` (ميزة منفصلة تماماً لم تُمسّ)؛ رابط "الإشعارات" (أيقونة Bell) جديد أُضيف لكل الأدوار الخمسة يشير إلى `/notifications`.
  - `resources/js/types/index.ts` — واجهة `NotificationItem` جديدة (`id`, `title`, `message`, `url`, `read_at`, `created_at`)؛ `SharedData.notifications` امتدت لتشمل `recent: NotificationItem[]`.
- **الاختبارات (18 اختباراً جديداً):**
  - `tests/Feature/Notification/NotificationTest.php` — تقديم مقترح يُشعِر مدير القسم المعني فقط (وليس مدير قسم آخر)؛ إعادة التقديم بعد تعديل تحمل رسالة مختلفة؛ اعتماد مقترح يُشعِر الفريق كاملاً + المنشئ + المشرف؛ تكليف ممتحن يُشعِر صاحب الحساب المرتبط ولا يفشل إن لم يكن له حساب؛ انتقال مشروع لحالة "جاهز للمناقشة" يُشعِر المشرف، بينما تحديث المشروع دون تغيير الحالة الفعلي لا يُرسِل شيئاً؛ مستخدم يرى إشعاراته فقط دون إشعارات غيره؛ تعليم إشعار كمقروء يعمل وممنوع على إشعار مستخدم آخر (404)؛ "تعليم الكل كمقروء" يُصفّر كل الإشعارات غير المقروءة؛ الزائر غير المسجَّل يُحوَّل لصفحة الدخول.
- **التحقق:** `php artisan test` → **305/306 ناجحة**. الفشل الوحيد المتبقي (`RBACTest > dept_manager cannot access admin panel`) **سابق على كل عمل هذا المشروع** (مؤكَّد مسبقاً عدة مرات في مراحل سابقة، غير مرتبط بهذا التغيير). `npm run build` نجح بدون أخطاء.
- **ما لم يُنفَّذ بعد (مؤجَّل لمراحل لاحقة):** بقية الأحداث الـ17 المذكورة في المواصفة الأصلية (جدولة مناقشة، اعتماد توثيقات، إخلاء طرف، طلبات إشراف...) تبقى مؤجَّلة لحين بناء أنظمتها الفعلية في المراحل اللاحقة؛ لا قناة بريد/broadcast للأحداث الجديدة الأربعة (قناة `database` فقط، بنفس نمط أغلب الإشعارات الموجودة)؛ أفكار المشاريع وطلبات الانضمام (Phase 5)، وبقية المراحل حتى Phase 8.

### ✅ #17 منصة دورة حياة مشروع التخرج — Phase 5: نظام أفكار المشاريع
- **تاريخ التنفيذ:** 2026-08-17
- **السياق:** المرحلة الخامسة من خطة الـ8 مراحل (`plans_to_execute.md#11`). أول مرحلة تمنح المشرف قدرة **نشر** بدل الاقتصار على المراجعة/الموافقة فقط — المشرف ينشر "فكرة مشروع" مستقلة، والطلاب يتصفّحون الأفكار المتاحة. لا علاقة مباشرة بعد بجدول `ProjectProposal`/`Project` — الربط الفعلي (تحويل فكرة إلى مشروع حقيقي) مؤجَّل لـ Phase 6 (طلبات الانضمام).
- **قاعدة البيانات (migration واحدة، جدول جديد):**
  - `project_ideas`: `faculty_member_id` (FK→faculty_members، `cascadeOnDelete`)، `title`، `description`، `specialization_id` (FK→specializations، `restrictOnDelete`)، `required_students_count` (tinyint، افتراضي 1)، `skills`، `keywords`، `notes` (نصوص اختيارية)، `status` (نص، افتراضي `available`؛ القيم: `available`/`reserved`/`completed`/`closed`).
- **Backend:**
  - `app/Models/ProjectIdea.php` (جديد) — ثوابت الحالة الأربع، علاقتا `facultyMember()` و`specialization()`.
  - `app/Policies/ProjectIdeaPolicy.php` (جديد، auto-discovery بنفس نمط `ProjectProposalPolicy` — بلا تسجيل يدوي في `AuthServiceProvider`) — المشرف يُنشئ/يعدّل/يحذف أفكاره فقط (تحقق ملكية عبر `faculty_member_id`)؛ الطالب يرى فقط الأفكار بحالة `available` (`view`/`viewAny`).
  - `app/Http/Requests/StoreProjectIdeaRequest.php` + `UpdateProjectIdeaRequest.php` — التحقق: عنوان/وصف/تخصص مطلوبة، `required_students_count` بين 1 و`SystemSetting::current()->max_students_per_project` (نفس الحد المستخدم أصلاً لفرق المقترحات)؛ `status` قابل للتعديل فقط من نموذج التحديث (الإنشاء يبدأ دائمًا بـ`available`).
  - `app/Http/Controllers/Supervisor/ProjectIdeaController.php` (جديد) — `index()` (أفكار المشرف نفسه فقط)، `store()`، `update()`، `destroy()` — بنفس نمط `Supervisor\ProposalController` الرقيق.
  - `app/Http/Controllers/Student/ProjectIdeaController.php` (جديد) — `index()` (تصفّح، حالة `available` فقط، فلترة اختيارية بالتخصص، ترقيم صفحات 12)، `show()` (تفاصيل فكرة واحدة، محمي بـ`ProjectIdeaPolicy::view`).
  - Routes جديدة: `GET/POST/PUT/DELETE /supervisor/ideas...` (ضمن مجموعة `role:supervisor` الموجودة أصلاً)، `GET /student/ideas`, `/student/ideas/{idea}` (ضمن مجموعة `role:student` الموجودة أصلاً) — لا مجموعات middleware جديدة.
  - `database/factories/ProjectIdeaFactory.php` (جديد) للاختبارات.
- **Frontend:**
  - `resources/js/lib/ideaStatusBadge.ts` (جديد) — ألوان/تسميات عربية للحالات الأربع، بنفس نمط `proposalStatusBadge.ts` الموجود.
  - `resources/js/pages/Supervisor/Ideas/Index.vue` (جديد) — بطاقات شبكية لأفكار المشرف + Modal واحد مشترك للإنشاء/التعديل (حقل الحالة يظهر فقط عند التعديل) بإعادة استخدام مكوّني `Modal.vue`/`ConfirmDelete.vue` الموجودين، بنفس نمط `Departments/Index.vue`.
  - `resources/js/pages/Student/Ideas/Index.vue` (جديد) — تصفّح بطاقات شبكية للأفكار المتاحة فقط، فلتر تخصص، ترقيم صفحات.
  - `resources/js/pages/Student/Ideas/Show.vue` (جديد) — تفاصيل فكرة واحدة (وصف كامل، مهارات، كلمات مفتاحية، ملاحظات المشرف)، مع تنويه صريح أن إرسال طلب انضمام آلي غير متاح بعد (ينتظر Phase 6).
  - `resources/js/components/AppSidebar.vue` — رابط "أفكار المشاريع" (أيقونة Lightbulb) جديد لكل من `supervisor` (→ `/supervisor/ideas`) و`student` (→ `/student/ideas`).
- **الاختبارات (15 اختبارًا جديدًا):**
  - `tests/Feature/ProjectIdea/ProjectIdeaTest.php` — نشر فكرة جديدة بنجاح وربطها بحساب المشرف الصحيح؛ المشرف يرى أفكاره فقط في قائمته؛ تعديل فكرة (بما في ذلك تغيير الحالة يدويًا) وحظر تعديل/حذف فكرة مشرف آخر (403)؛ حذف فكرة المالك بنجاح؛ التحقق من الحقول المطلوبة وحد `required_students_count` الأعلى من إعداد النظام؛ `dept_staff` ممنوع بالكامل من مسار النشر (403 عبر `RoleMiddleware`، ليس عبر Policy)؛ الطالب يرى فقط الأفكار المتاحة عند التصفّح (لا محجوزة/مكتملة/مغلقة)؛ الفلترة بالتخصص تعمل؛ عرض تفاصيل فكرة متاحة ينجح، وحظر عرض تفاصيل فكرة غير متاحة (403)؛ المشرف ممنوع من مسار تصفّح الطالب والعكس صحيح أصلاً (403)؛ الزائر غير المسجَّل يُحوَّل لصفحة الدخول من كلا المسارين.
  - أُعيد استخدام الدوال المساعدة العامة الموجودة `makeLoggedInSupervisor()` (من `SupervisorPortalTest.php`) و`loggedInStudent()` (من `StudentProposalTest.php`) دون تكرار — يعملان لأن Pest يحمّل كل ملفات الاختبار في عملية واحدة.
- **التحقق:** `php artisan test` → **320/321 ناجحة**. الفشل الوحيد المتبقي (`RBACTest > dept_manager cannot access admin panel`) **سابق على كل عمل هذا المشروع** (مؤكَّد مسبقًا عدة مرات في مراحل سابقة، غير مرتبط بهذا التغيير). `npm run build` نجح بدون أخطاء. الميغريشن مُتحقَّق منه مباشرة على قاعدة MariaDB الفعلية.
- **قرارات نطاق محسومة عمدًا:** لا حذف "ناعم" (`is_deleted`) للأفكار — حذف مباشر لأنه لا يوجد بعد أي جدول يعتمد على `project_ideas` كمفتاح خارجي (سيُعاد النظر عند بناء Phase 6 إن احتاج الأمر)؛ لا صفحة إشراف/رقابة لـ`dept_manager`/`super_admin` على الأفكار — لم تُطلب في نطاق هذه المرحلة؛ تغيير حالة الفكرة (متاحة→محجوزة→مكتملة/مغلقة) يدوي بالكامل من المشرف حاليًا — الأتمتة عند قبول طلب انضمام تنتظر Phase 6 تحديدًا.
- **ما لم يُنفَّذ بعد (مؤجَّل لمراحل لاحقة):** طلبات الانضمام الفعلية من الطالب (`project_idea_requests`، Phase 6)، Versioning وحجز الأفكار (`proposal_reservations`، Phase 7)، توسيع كشف التشابه (Phase 8).

### ✅ #18 منصة دورة حياة مشروع التخرج — Phase 6: طلبات الانضمام
- **تاريخ التنفيذ:** 2026-08-17
- **السياق:** المرحلة السادسة من خطة الـ8 مراحل (`plans_to_execute.md#11`). الجسر بين تصفّح فكرة (Phase 5) والالتحاق الفعلي بها: الطالب يرسل رسالة طلب انضمام، والمشرف صاحب الفكرة يقبل أو يرفض.
- **قرار حدود محسوم عمدًا بين Phase 6 وPhase 7:** نص الخطة الأصلية أن جدول `proposal_reservations` (Phase 7) هو من "يربط الطالب بالفكرة تحديدًا ويتتبّع حالة الحجز" رسميًا عبر دورة حياة مخصَّصة (محجوز/قيد المراجعة/معتمد/متروك/محرَّر/منتهٍ). لذلك **لم يُنشأ أي `ProjectProposal`/`Project` فعلي عند قبول الطلب في هذه المرحلة** — القبول هنا يعني فقط: تحديث حالة الطلب لـ`accepted`، تحديث حالة الفكرة، وإشعار الطالب. التحويل الفعلي لحجز مُعتمَد (وما يترتّب عليه لاحقًا من مقترح/مشروع حقيقي) يبقى مسؤولية Phase 7 تحديدًا كما تنص الخطة، تجنبًا لتكرار منطق سيُعاد بناؤه هناك بشكل مختلف.
- **قاعدة البيانات (migration واحدة، جدول جديد):**
  - `project_idea_requests`: `project_idea_id` (FK→project_ideas، `cascadeOnDelete`)، `student_id` (FK→students، `cascadeOnDelete`)، `message` (نص اختياري)، `status` (نص، افتراضي `pending`؛ القيم: `pending`/`accepted`/`rejected`)، `decided_at` (تاريخ/وقت اختياري).
- **Backend:**
  - `app/Models/ProjectIdeaRequest.php` (جديد) — ثوابت الحالة الثلاث، علاقتا `idea()` و`student()`.
  - `app/Models/ProjectIdea.php` — علاقة `requests()` جديدة + دالة `acceptedRequestsCount()`.
  - `app/Models/Student.php` — علاقة `ideaRequests()` + دالة `hasAcceptedIdeaRequest()` جديدتان، تُستخدَمان لتطبيق قاعدة "لا التزام مزدوج" الممتدة من قاعدة "لا مشروع نشط مزدوج" الموجودة أصلاً لتشمل الأفكار المقبولة أيضًا.
  - `app/Policies/ProjectIdeaRequestPolicy.php` (جديد، كان مُتوقَّعًا صراحة في الخطة الأصلية) — `create(User, ProjectIdea)`: الطالب فقط، والفكرة يجب أن تكون `available`، ولا مشروع نشط (`hasActiveProposal()`) ولا طلب مقبول آخر (`hasAcceptedIdeaRequest()`) لديه، ولا طلب معلَّق/مقبول سابق لنفس الفكرة؛ `viewAny(User, ProjectIdea)`: المشرف المالك فقط؛ `decide(User, ProjectIdeaRequest)`: المشرف المالك فقط، وطالما الطلب لا يزال `pending`. استُخدم نمط Laravel القياسي `authorize('create', [ProjectIdeaRequest::class, $idea])` لأن الـability تخص نموذجًا فرعيًا (Request) مرتبطًا بنموذج أب (Idea) له Policy منفصلة أصلًا.
  - `app/Policies/ProjectIdeaPolicy.php::view()` — تعديل: الطالب الذي قدَّم طلبًا (بأي حالة) على فكرة يحتفظ بحق رؤيتها حتى بعد خروجها من حالة `available` (سابقًا كانت `view()` تحجب الفكرة فور تغيّر حالتها، فيفقد الطالب القدرة على متابعة فكرته الخاصة بعد قبولها — عطل منطقي اكتُشف وأُصلح أثناء هذه المرحلة قبل الشحن).
  - `app/Http/Controllers/Student/ProjectIdeaRequestController.php` (جديد) — `store()` (إرسال طلب + إشعار المشرف)، `index()` ("طلباتي" — كل طلبات الطالب الحالي فقط).
  - `app/Http/Controllers/Supervisor/ProjectIdeaRequestController.php` (جديد) — `index($idea)` (طلبات فكرة واحدة، للمالك فقط)، `accept()` (يحدّث حالة الطلب + يعيد حساب حالة الفكرة: `reserved` عند أول قبول، `closed` تلقائيًا عند بلوغ `required_students_count` + إشعار الطالب)، `reject()` (يحدّث حالة الطلب دون أثر على حالة الفكرة + إشعار الطالب).
  - `app/Http/Controllers/Supervisor/ProjectIdeaController.php::index()` — إضافة `withCount` لعدد الطلبات المعلَّقة لكل فكرة (`pending_requests_count`) لعرضها كشارة في القائمة.
  - `app/Http/Controllers/Student/ProjectIdeaController.php::show()` — إضافة `myRequest` (آخر طلب للطالب الحالي على هذه الفكرة إن وُجد) و`canApply` (نتيجة Policy) للواجهة.
  - `app/Notifications/ProjectIdeaRequestSubmitted.php` (جديد) — للمشرف عند تقديم طلب، بنفس غلاف `title`/`message`/`url` من Phase 4.
  - `app/Notifications/ProjectIdeaRequestDecided.php` (جديد) — للطالب عند القبول أو الرفض (رسالة مختلفة حسب النتيجة)، بنفس الغلاف.
  - Routes جديدة: `POST /student/ideas/{idea}/requests`, `GET /student/idea-requests` (ضمن مجموعة `role:student` الموجودة)؛ `GET /supervisor/ideas/{idea}/requests`, `PATCH /supervisor/idea-requests/{ideaRequest}/accept`, `PATCH /supervisor/idea-requests/{ideaRequest}/reject` (ضمن مجموعة `role:supervisor` الموجودة) — لا مجموعات middleware جديدة.
  - `database/factories/ProjectIdeaRequestFactory.php` (جديد) للاختبارات.
- **Frontend:**
  - `resources/js/pages/Student/Ideas/Show.vue` — استُبدل التنويه الثابت "تواصل مع المشرف يدويًا" بثلاث حالات فعلية: نموذج إرسال طلب (رسالة اختيارية) إذا كان `canApply` صحيحًا، عرض حالة الطلب الحالي إن وُجد (قيد المراجعة/مقبول/مرفوض)، أو رسالة توضيحية إذا تعذّر التقديم (فكرة غير متاحة أو التزام آخر قائم).
  - `resources/js/pages/Student/Ideas/MyRequests.vue` (جديد) — جدول "طلباتي" (الفكرة، المشرف، الحالة، التاريخ).
  - `resources/js/pages/Supervisor/Ideas/Requests.vue` (جديد) — قائمة طلبات فكرة واحدة (اسم الطالب، رقم القيد، الرسالة، الحالة) مع زرَّي قبول/رفض للطلبات المعلَّقة فقط.
  - `resources/js/pages/Supervisor/Ideas/Index.vue` — زر "مراجعة الطلبات" جديد لكل بطاقة فكرة مع شارة عدد الطلبات المعلَّقة.
  - `resources/js/components/AppSidebar.vue` — رابط "طلباتي" (أيقونة ClipboardList) جديد لدور `student` فقط (→ `/student/idea-requests`).
- **الاختبارات (15 اختبارًا جديدًا):**
  - `tests/Feature/ProjectIdea/ProjectIdeaRequestTest.php` — تقديم طلب ناجح + إشعار المشرف؛ منع طلب مكرر لنفس الفكرة أثناء التعليق؛ منع الطلب لصاحب مشروع نشط أو صاحب طلب فكرة مقبول آخر؛ منع الطلب لفكرة غير متاحة؛ المشرف يرى طلبات فكرته فقط ولا يرى طلبات فكرة مشرف آخر (403)؛ قبول الطلب الوحيد المطلوب يُغلق الفكرة تلقائيًا ويُشعِر الطالب؛ قبول أحد عدة طلبات مطلوبة يجعل الفكرة "محجوزة" فقط دون إغلاقها؛ الرفض لا يؤثر على حالة الفكرة ويُشعِر الطالب؛ لا يمكن اتخاذ قرار على طلب سبق البتّ فيه (403)؛ منع مشرف من البتّ في طلب فكرة لا يملكها (403)؛ الطالب يرى طلباته الخاصة فقط في "طلباتي"؛ طالب سبق أن قدَّم طلبًا يبقى قادرًا على رؤية صفحة الفكرة حتى بعد خروجها من حالة "متاحة" (تحقّق مباشر من إصلاح Policy)؛ الزائر غير المسجَّل يُحوَّل لصفحة الدخول من كلا المسارين.
  - أُعيد استخدام `ideaDeps()`/`makeLoggedInSupervisor()`/`loggedInStudent()` الموجودة من الاختبارات السابقة دون تكرار.
- **التحقق:** `php artisan test` → **335/336 ناجحة**. الفشل الوحيد المتبقي (`RBACTest > dept_manager cannot access admin panel`) **سابق على كل عمل هذا المشروع** (مؤكَّد مسبقًا عدة مرات في مراحل سابقة، غير مرتبط بهذا التغيير). `npm run build` نجح بدون أخطاء. الميغريشن مُتحقَّق منه مباشرة على قاعدة MariaDB الفعلية.
- **ما لم يُنفَّذ بعد (مؤجَّل لمراحل لاحقة):** تحويل الطلب المقبول إلى حجز/مقترح/مشروع فعلي بدورة حياة مخصَّصة (`proposal_reservations`، Phase 7 تحديدًا — القرار موثَّق أعلاه)، توسيع كشف التشابه (Phase 8).

### ✅ #19 منصة دورة حياة مشروع التخرج — Phase 7: Versioning وحجز الأفكار
- **تاريخ التنفيذ:** 2026-08-17
- **السياق:** المرحلة السابعة من خطة الـ8 مراحل (`plans_to_execute.md#11`). تُكمِل الحلقة المؤجَّلة من Phase 6: قبول طلب الانضمام الآن يُنشئ **حجزًا فعليًا** (`ProposalReservation`) يربط الطالب بالفكرة بدورة حياة مستقلة، بدل الاكتفاء بتحديث حالة الطلب فقط.
- **قرار نطاق محسوم عمدًا (التزام حرفي بنص الخطة):** نص القرار المعماري المعتمد يقول إن جدول الحجز الجديد "يربط الطالب بالفكرة **ويتتبّع حالة الحجز فقط**" — لذلك **لم يُربط الحجز بإنشاء `ProjectProposal`/`Project` فعلي بعد** في هذه المرحلة أيضًا. لا جدول نسخ جديد للمقترح نفسه (آلية `replaces_proposal_id` الموجودة تبقى كما هي تمامًا، بلا أي تعديل) — الحجز طبقة تتبّع إضافية فوقها، تمامًا كما ينص القرار الأصلي.
- **قاعدة البيانات (migration واحدة، جدول جديد):**
  - `proposal_reservations`: `project_idea_id` (FK→project_ideas، `cascadeOnDelete`)، `student_id` (FK→students، `cascadeOnDelete`)، `project_idea_request_id` (FK→project_idea_requests، nullable، `nullOnDelete` — يربط الحجز بالطلب الذي أنشأه)، `status` (نص، افتراضي `reserved`؛ القيم الست: `reserved`/`under_review`/`approved`/`abandoned`/`released`/`finished`).
- **Backend:**
  - `app/Models/ProposalReservation.php` (جديد) — ثوابت الحالات الست + ثابت `ACTIVE_STATUSES` (reserved/under_review/approved — الحالات التي تُحتسَب كـ"التزام نشط")، علاقات `idea()`/`student()`/`ideaRequest()`.
  - `app/Models/ProjectIdea.php` — استُبدلت `acceptedRequestsCount()` (كانت تُحسَب من عدّاد طلبات مقبولة تاريخيًا، ثابت لا يتراجع) بـ`reservations()` + `activeReservationsCount()` + `syncStatusFromReservations()` (تُعيد حساب حالة الفكرة: مغلقة/محجوزة/متاحة من عدد **الحجوزات النشطة فعليًا** — هذا ما يجعل "تحرير الحجز" يعيد فتح الفكرة بشكل صحيح).
  - `app/Models/Student.php` — استُبدلت `hasAcceptedIdeaRequest()` (Phase 6، تبقى `true` للأبد حتى بعد التخلي) بـ`reservations()` + `hasActiveReservation()` (تتحقق من الحجوزات النشطة فقط) — **إصلاح منطقي مقصود**: التخلي عن الحجز (`abandoned`) يُحرِّر الطالب فورًا لطلب فكرة أخرى، دون انتظار تحرير المشرف للفكرة.
  - `app/Models/ProjectIdeaRequest.php` — علاقة `reservation(): HasOne` جديدة (عكس `project_idea_request_id`).
  - `app/Policies/ProjectIdeaRequestPolicy.php::create()` — تحديث الفحص من `hasAcceptedIdeaRequest()` إلى `hasActiveReservation()` لتفعيل الإصلاح أعلاه.
  - `app/Policies/ProposalReservationPolicy.php` (جديد، كان مُتوقَّعًا صراحة في الخطة) — `markUnderReview`/`abandon`: الطالب المالك فقط، بقيود على الحالة الحالية (`abandon` ممنوع بعد `approved`)؛ `approve`/`release`/`finish`: المشرف المالك فقط، كل فعل مقيَّد بحالة سابقة محدَّدة (`approve` من `under_review` فقط، `release` من `abandoned` فقط، `finish` من `approved` فقط) — يمنع أي تخطٍّ للترتيب الطبيعي للحالات.
  - `app/Http/Controllers/Supervisor/ProjectIdeaRequestController.php::accept()` — مُعدَّل: بعد قبول الطلب، يُنشئ سجل `ProposalReservation` (حالة `reserved`) ويستدعي `syncStatusFromReservations()` بدل التحديث اليدوي السابق لحالة الفكرة.
  - `app/Http/Controllers/Student/ProposalReservationController.php` (جديد) — `show()` ("حجزي" — أحدث حجز للطالب الحالي)، `markUnderReview()`، `abandon()`.
  - `app/Http/Controllers/Supervisor/ProposalReservationController.php` (جديد) — `approve()`، `release()` (يستدعي `syncStatusFromReservations()` بعد التحرير لإعادة فتح الفكرة)، `finish()`.
  - Routes جديدة: `GET /student/reservation`, `PATCH /student/reservations/{id}/under-review`, `PATCH /student/reservations/{id}/abandon` (ضمن مجموعة `role:student`)؛ `PATCH /supervisor/reservations/{id}/approve`, `/release`, `/finish` (ضمن مجموعة `role:supervisor`) — لا مجموعات middleware جديدة.
  - `database/factories/ProposalReservationFactory.php` (جديد) للاختبارات.
- **Frontend:**
  - `resources/js/pages/Student/Ideas/Reservation.vue` (جديد) — صفحة "حجزي": تفاصيل الفكرة المحجوزة + شارة حالة + زرّا "بدء العمل على المقترح" (reserved→under_review) و"التخلي عن الحجز" (قبل الاعتماد فقط)، أو حالة فارغة إن لم يوجد حجز.
  - `resources/js/pages/Supervisor/Ideas/Requests.vue` — قسم جديد لكل طلب مقبول يعرض شارة حالة الحجز + أزرار "اعتماد الحجز"/"تحرير الحجز"/"إنهاء الحجز" حسب الحالة الحالية فقط.
  - `resources/js/components/AppSidebar.vue` — رابط "حجزي" (أيقونة Bookmark) جديد لدور `student` فقط (→ `/student/reservation`).
- **الاختبارات (16 اختبارًا جديدًا + تحديث اختبار Phase 6 واحد):**
  - `tests/Feature/ProjectIdea/ProposalReservationTest.php` — قبول طلب يُنشئ حجزًا بحالة `reserved`؛ الطالب يُعلن الجدية (`under_review`) ولا يمكنه ذلك على حجز غيره (403)؛ التخلي مسموح من `reserved`/`under_review` وممنوع بعد `approved` (403)؛ التخلي يُحرِّر الطالب فورًا لطلب فكرة أخرى (تحقّق مباشر من الإصلاح)؛ اعتماد المشرف مسموح من `under_review` فقط ويُمنع من `reserved` (403)؛ تحرير حجز متروك يُعيد فتح الفكرة (`closed→available`)، ويُمنع تحرير حجز غير متروك (403)؛ إنهاء حجز معتمَد يعمل ويُمنع على غير المعتمَد (403)؛ مشرف لا يملك الفكرة يُمنع من أي إجراء (403)؛ صفحة "حجزي" تعرض الحجز الحالي أو حالة فارغة؛ الزائر غير المسجَّل يُحوَّل لصفحة الدخول.
  - تحديث `tests/Feature/ProjectIdea/ProjectIdeaRequestTest.php` — الاختبار *"student with an already-accepted idea request cannot request another idea"* أُعيد تسميته وتحديثه إلى *"student with an active reservation cannot request another idea"* مع إنشاء سجل `ProposalReservation` مطابق فعليًا (بدل الاكتفاء بحالة الطلب `accepted` فقط) — نتيجة مباشرة ومتوقَّعة لاستبدال آلية الفحص في `ProjectIdeaRequestPolicy::create()`، وليست عطلاً.
- **التحقق:** `php artisan test` → **351/352 ناجحة**. الفشل الوحيد المتبقي (`RBACTest > dept_manager cannot access admin panel`) **سابق على كل عمل هذا المشروع** (مؤكَّد مسبقًا عدة مرات في مراحل سابقة، غير مرتبط بهذا التغيير). `npm run build` نجح بدون أخطاء. الميغريشن مُتحقَّق منه مباشرة على قاعدة MariaDB الفعلية.
- **ما لم يُنفَّذ بعد (مؤجَّل لعمل مستقبلي مفتوح خارج نطاق المراحل الثمانية):** لا تحويل تلقائي من حجز معتمَد إلى `ProjectProposal`/`Project` فعلي — الحجز يبقى طبقة تتبّع منفصلة كما ينص القرار المعتمد صراحةً؛ توسيع كشف التشابه (Phase 8 — المرحلة الأخيرة المتبقية في الخطة).

### ✅ #20 منصة دورة حياة مشروع التخرج — Phase 8: كشف التشابه (المرحلة الأخيرة — اكتملت الخطة كاملة)
- **تاريخ التنفيذ:** 2026-08-17
- **السياق:** المرحلة الثامنة والأخيرة من خطة الـ8 مراحل (`plans_to_execute.md#11`). توسيع `SearchService::detectSimilarity()` الموجودة فعلاً (كانت تُستخدَم فقط داخل `ProjectController` بمطابقة نصية بسيطة على العنوان) لتُنتج **نسبة تشابه %** مبنية على تداخل الكلمات بين العنوان والوصف، وتقارن ضد **المشاريع والمقترحات معًا** بدل المشاريع فقط، ثم رُبطت لأول مرة بمسارات تقديم/تعديل المقترحات (الموظف والطالب) التي لم تكن تستخدمها إطلاقًا من قبل.
- **لا نظام جديد — توسيع خدمة موجودة فقط**، كما ينص القرار المعماري المعتمد؛ لا مكتبة NLP خارجية ولا حزمة Composer جديدة.
- **الخوارزمية (بلا اعتماديات جديدة):**
  - تجزئة العنوان/الوصف إلى كلمات (Unicode-aware عبر `preg_split` بنمط `\p{L}\p{N}`، يدعم العربية والإنجليزية معًا)، بعد تحويلها لحروف صغيرة وحذف الكلمات الأقصر من حرفين.
  - نسبة تشابه لكل مرشَّح = مؤشر Jaccard (تقاطع/اتحاد الكلمات) على العنوان بوزن 70% + على الوصف بوزن 30% (أو العنوان فقط إن غاب أحد الوصفين) — مُقرَّبة لعدد صحيح 0–100.
  - ترشيح أولي بـ`LIKE` على العنوان الكامل + كل كلمة مفردة منه (تحسين استرجاع عن المطابقة النصية الكاملة القديمة)، ثم حساب النسبة الدقيقة لكل مرشَّح، مع عتبة دنيا 30% لإظهار التحذير، وأعلى 5 نتائج مرتّبة تنازليًا.
- **Backend:**
  - `app/Services/SearchService.php::detectSimilarity()` — توقيع جديد: `(string $title, string $description = '', ?int $excludeProjectId = null, ?int $excludeProposalId = null): SupportCollection`. يُرجع الآن مصفوفات جاهزة `['type' => 'project'|'proposal', 'id', 'title', 'academic_year', 'department', 'similarity_percent']` بدل نماذج Eloquent خام — **تغيير كاسر متعمَّد** لتمكين حقل النسبة ودمج نوعين مختلفين من النماذج في نتيجة واحدة (موثَّق كتحديث اختباري صريح أدناه، بنفس نمط تحديثات الاختبارات في المراحل السابقة).
  - `ProjectController::store()`/`update()` — تمرير الوصف الآن أيضًا (لم يكن يُمرَّر سابقًا)، ودالة خاصة جديدة `withUrls()` تُلحق رابط عرض آمن بكل مرشَّح (`projects.show` أو `proposals.show` — كلاهما متاح لكل الأدوار التي تستخدم هذا الكونترولر).
  - `ProjectProposalWebController` (الموظف) — **ربط جديد بالكامل** لم يكن موجودًا إطلاقًا من قبل: حقن `SearchService`، حساب `detectSimilarity()` في `store()`/`update()`، نفس نمط `withUrls()`.
  - `Student\ProposalController` — **ربط جديد بالكامل** أيضًا: نفس الحساب في `store()`/`update()`، لكن دالة `withUrls()` هنا تُبقي `url = null` لأي مرشَّح من نوع `proposal` (لأن Policy الطالب لا تسمح برؤية مقترح فريق آخر) بينما تسمح بربط مرشَّحات `project` بأمان (`projects.show` بلا قيد دور) — **قرار أمان صريح لمنع تسريب رابط 403**.
- **Frontend:**
  - `resources/js/types/index.ts::SimilarProject` — إعادة تسمية كاملة (`project_title`→`title`) وحقول جديدة (`type`, `similarity_percent`, `url: string | null`).
  - `resources/js/components/SimilarityWarning.vue` — يعرض الآن شارة نوع (مشروع/مقترح) ونسبة التشابه لكل عنصر، ويعرض نصًا عاديًا غير قابل للنقر عندما `url = null` بدل رابط قد يفشل بـ403.
  - **إصلاح عرض مكتشَف أثناء هذه المرحلة:** التحذير في `ProjectController` كان يُخزَّن فعليًا في الجلسة منذ البداية لكنه **لم يكن يظهر أبدًا** — `store()`/`update()` يُعيدان التوجيه إلى `projects.show`، بينما مكوّن العرض كان موجودًا فقط في `Projects/Index.vue` غير المُستهدَفة بالتحويل. أُضيف عرض `SimilarityWarning` إلى `Projects/Show.vue` (يصلح المسارين القديم والجديد معًا)، وإلى `Proposals/Show.vue` و`Student/Proposal/Show.vue` (جديدتان بالكامل لأن الربط نفسه جديد في هذه المرحلة).
- **الاختبارات (10 اختبارات جديدة + تحديث 2 اختبارين قديمين):**
  - تحديث `tests/Feature/Search/SearchTest.php` — الاختباران القديمان *"similarity detection finds matching titles"* و*"ignores current project when editing"* حُدِّثا للوصول بصيغة المصفوفة الجديدة (`['title']` بدل `->project_title`) بدل الكائن القديم — نتيجة مباشرة للتغيير الكاسر الموثَّق أعلاه، وليست عطلاً. + اختباران جديدان: العثور على مقترح مشابه مع نسبة > 50%، واستبعاد المقترحات المُستبدَلة (`superseded`) من المرشَّحين.
  - `tests/Feature/Search/SimilarityDetectionTest.php` (جديد، 6 اختبارات) — إنشاء مشروع مشابه لمقترح موجود يُرسِل تحذيرًا مرتبطًا برابط؛ إنشاء مقترح (موظف) مشابه لمشروع موجود كذلك؛ تقديم مقترح (طالب) مشابه لمشروع موجود يحصل على رابط قابل للنقر؛ تقديم مقترح (طالب) مشابه لمقترح فريق آخر يحصل على تحذير **بلا رابط** (تحقّق مباشر من قرار الأمان)؛ تعديل مشروع يستثني نفسه من نتائج التشابه الخاصة به؛ لا تحذير عند عدم وجود أي تشابه فعلي.
- **التحقق:** `php artisan test` → **359/360 ناجحة**. الفشل الوحيد المتبقي (`RBACTest > dept_manager cannot access admin panel`) **سابق على كل عمل هذا المشروع** (مؤكَّد مسبقًا عدة مرات في كل مرحلة سابقة، غير مرتبط بهذا التغيير). `npm run build` نجح بدون أخطاء TypeScript.
- **🎉 هذه آخر مرحلة في خطة "منصة دورة حياة مشروع التخرج" ذات الثماني مراحل (`plans_to_execute.md#11`) — الخطة بأكملها منفَّذة ومُختبَرة الآن (Phase 1 → Phase 8).** العمل المفتوح الوحيد المتبقي خارج نطاق الخطة نفسها: التحويل التلقائي من حجز معتمَد (Phase 7) إلى `ProjectProposal`/`Project` فعلي — قرار نطاق مؤجَّل عمدًا وموثَّق في كل من Phase 6 وPhase 7، ولم يكن جزءًا من الثماني مراحل المطلوبة أصلاً.

### ✅ #21 إضافة طالب فردي بنفس حقول الاستيراد الجماعي (منع التضارب بين المسارين)
- **تاريخ التنفيذ:** 2026-08-19
- **السياق:** طلب مستقل خارج الثماني مراحل — يحتاج القسم إمكانية إضافة طالب واحد يدويًا (بدل الاستيراد الجماعي عبر Excel فقط)، بشرط أن يستخدم نفس مجموعة الحقول المستخدمة في الاستيراد الجماعي تمامًا (لمنع دخول بيانات بمخطط مختلف عبر المسارين)، وبنفس نمط تصميم نموذج "إضافة مقترح" الحالي.
- **إعادة هيكلة لمنع التضارب فعليًا (لا مجرد نسخ الحقول):** كان منطق إنشاء الطالب + حساب الدخول (`Student::create` + `User::create` + `assignRole('student')` + إشعار `StudentAccountCreated`، كل ذلك داخل معاملة DB واحدة) موجودًا فقط داخل `StudentsImport::createStudentAndAccount()` الخاصة بالاستيراد الجماعي. استُخرج هذا المنطق إلى خدمة مشتركة جديدة `app/Services/StudentAccountService::create()`، وأصبح **الاستيراد الجماعي والإضافة الفردية يستدعيان نفس الخدمة بالضبط** — أي تعديل مستقبلي على منطق الإنشاء ينعكس تلقائيًا على المسارين معًا، فلا يمكن أن يتضاربا.
- **Backend:**
  - `app/Services/StudentAccountService.php` (جديد) — `create(array $data): Student`، منطق مستخرج حرفيًا من الاستيراد الجماعي.
  - `app/Imports/StudentsImport.php` — `createStudentAndAccount()` أُعيدت كتابتها لتستدعي `StudentAccountService::create()` بدل تكرار المنطق؛ سلوكها الفعلي والاختبارات القائمة عليها (`StudentImportTest`) لم يتغيرا.
  - `app/Http/Requests/StoreStudentRequest.php` (جديد) — نفس قواعد تحقّق `StudentsImport::validate()` تمامًا: `full_name` مطلوب، `national_id` رقم وطني من 12 رقماً وفريد، `registration_number` بطول `SystemSetting::current()->registration_number_length` (ديناميكي) وفريد، `department_id`/`specialization_id` موجودان مع تحقّق إضافي (`withValidator`) أن التخصص فعلاً تابع للقسم المُرسَل، `semester` ضمن `Semester::pluck('name')`، `date_of_birth` تاريخ صالح. دالة `prepareForValidation()` **تفرض** `department_id = $authUser->department_id` عند `dept_manager` بغضّ النظر عمّا أُرسل من الواجهة (حماية خادمية مستقلة عن الواجهة، وليست ثقة بها فقط).
  - `app/Http/Controllers/StudentController.php` — دالة `store()` جديدة تستدعي `StudentAccountService`؛ `index()` تُرسل الآن `departments` (قسم واحد فقط لـ`dept_manager`، كل الأقسام لـ`super_admin`) وأضافت `department_id` لكل عنصر في `specializations` (لازمة للتصفية المتتالية قسم→تخصص في الواجهة).
  - `routes/web.php` — `POST /students` باسم `students.store`، ضمن نفس مجموعة الميدلوير الحالية للطلاب (`role:dept_manager,super_admin`) — نفس نطاق صلاحية الاستيراد الجماعي.
- **Frontend:**
  - `resources/js/components/Students/StudentFormModal.vue` (جديد) — بنفس بنية/تنسيق `ProposalFormModal.vue` حرفيًا (Modal + `useForm` + إعادة تعيين عند الفتح + تصنيف حقول بمجموعات `grid grid-cols-2` + تذييل إلغاء/حفظ)؛ قائمة تخصصات متتالية تتصفّى تلقائيًا حسب القسم المختار؛ حقل القسم يُقفَل تلقائيًا (`disabled`) عندما يكون لدى المستخدم قسم واحد فقط متاح (`dept_manager`).
  - `resources/js/pages/Students/Index.vue` — زر جديد "+ إضافة طالب فردي" بجانب زر الاستيراد الجماعي القائم، يفتح `StudentFormModal`؛ عنوان الصفحة عُدِّل من "الطلاب المستوردون" إلى "الطلاب" (لم يعد كل الطلاب بالضرورة عبر الاستيراد فقط).
- **الاختبارات (9 اختبارات جديدة):** `tests/Feature/Student/StudentAddTest.php` — `dept_manager` يضيف طالبًا لقسمه بنجاح (مع تحقّق إنشاء حساب User + دور student)؛ `dept_manager` يُمنع فعليًا من إضافة طالب لقسم آخر (فرض `department_id` خادميًا يجعل التخصص المُرسَل غير مطابق فيُرفض)؛ `dept_staff` ممنوع تمامًا (403)؛ رفض الرقم الوطني غير المكوَّن من 12 رقماً؛ رفض رقم قيد لا يطابق الطول المُعدّ؛ رفض رقم وطني مكرر؛ رفض تخصص لا ينتمي للقسم المُرسَل؛ رفض فصل دراسي غير معروف؛ صفحة قائمة الطلاب ترسل `departments` و`department_id` داخل كل تخصص.
- **خطأ اختبار مكتشَف ومُصحَّح أثناء الكتابة (وليس عطلاً في كود التطبيق):** أول استدعاء لـ`SystemSetting::current()` في نفس الاختبار (قبل إنشاء أي صف في `system_settings`) كان يُعيد نموذجًا في الذاكرة بقيمة `registration_number_length = null` (لأن `firstOrCreate()` لا يُعيد تحميل القيم الافتراضية المطبَّقة على مستوى قاعدة البيانات بعد الإدراج)، بينما الاستدعاء الثاني داخل `StoreStudentRequest` (بعد وجود الصف فعليًا) كان يُعيد `9` الصحيحة — ما تسبَّب بتضارب طول رقم القيد بين الطرفين. الإصلاح: `beforeEach` في الاختبار يستدعي `SystemSettingSeeder` صراحةً أولًا (بنفس نمط `StudentImportTest.php` القائم). لا يؤثر هذا على الإنتاج إطلاقًا لأن `DatabaseSeeder` يستدعي `SystemSettingSeeder` دائمًا عند التهيئة الأولى.
- **التحقق:** `php artisan test` → **368/369 ناجحة**. الفشل الوحيد المتبقي (`RBACTest > dept_manager cannot access admin panel`) **سابق على كل عمل هذا المشروع**، غير مرتبط بهذا التغيير. `npm run build` نجح بدون أخطاء (بُني في 1m30s).
