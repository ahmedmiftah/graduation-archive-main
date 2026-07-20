# Project Task Plan ✅

## ✅ Implemented Changes

| # | Change Description | Status |
|---|--------------------|--------|
| 1 | **Sidebar** – Change label "التخصصات" to "الأقسام" for **admin** only. | ✅ |
| 2 | **Sidebar** – Restore label "التخصصات" for **dept_manager** (keep other roles unchanged). | ✅ |
| 3 | **Reports (Department.vue)** – Enable department filter for **admin** (`isSuperAdmin` flag). The dropdown is now enabled for admin and disabled for other roles. | ✅ |
| 4 | **Reports** – Adjust `filteredSupervisors` to show all supervisors for admin, otherwise filter by the user’s department. | ✅ |
| 5 | **FilterPanel.vue** – Add `filteredDepartments` and `authUser` logic. Show all departments for admin, otherwise limit to the user’s department and disable the dropdown. | ✅ |
| 6 | **Projects/Create.vue** – Add department isolation: dropdown disabled for non‑admin users, pre‑selected to the user’s department, and populated with `filteredDepartments`. | ✅ |
| 7 | **Projects/Edit.vue** – Same department isolation as in the create view (disabled for non‑admin, pre‑selected to user’s department). | ✅ |
| 8 | **SearchService.php** – Enforce departmental isolation in `searchProjects` by automatically setting `filters['department_id']` to the authenticated user’s department when the user is not `super_admin`. | ✅ |
| 9 | **SearchController.php (suggestions)** – Restrict autocomplete suggestions to the user’s department unless the user is admin. | ✅ |
| 10 | **Reports/Specializations.vue** – Add admin‑only department filter logic similar to the department report. | ✅ |
| 11 | **Department/Index.vue** – Display only the departments the user belongs to (admin sees all). | ✅ |
| 12 | **Reports UI** – Hide the "Reset" button when no filter is applied for admin, and keep it for other roles. | ✅ |
| 13 | **Sidebar icons** – Ensure the building icon (`Building2`) matches the updated label for each role. | ✅ |

---

### Additional updates (from recent edit)
- [x] Update **AppSidebar.vue**: replace "الأقسام" with "التخصصات" for **super_admin** and **dept_manager**.
- [x] Update **Departments/Index.vue**:
  - Change breadcrumb title to "التخصصات".
  - Change `<Head>` title to "التخصصات".
  - Change header `<h1>` text to "إدارة التخصصات".
- [x] Update **Departments/Create.vue**: change breadcrumb title to "التخصصات".
- [x] Update **Departments/Edit.vue**: change breadcrumb title to "التخصصات".
- [x] Update **Welcome.vue**: modify description in "تقارير وإحصائيات" card to reference "التخصصات" instead of "الأقسام".

All listed changes have been applied and verified.
