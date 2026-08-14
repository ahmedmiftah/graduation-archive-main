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

### ⚠️ ملاحظة غير منفَّذة — نص PDF العربي غير مفهوم (dompdf)
- لم تُطلب هذه النقطة ضمن هذه الدفعة (#4–#8). هذه مشكلة جوهرية في مكتبة dompdf v3.1.5 نفسها (لا تدعم تشكيل الحروف العربية/bidi reordering) — تحتاج قراراً منفصلاً بين معالجة النص العربي يدوياً قبل dompdf أو التحول لمحرك PDF مختلف (Browsershot/Chromium)، وتم شرح الخيارين للمستخدم مسبقاً دون تنفيذ.
