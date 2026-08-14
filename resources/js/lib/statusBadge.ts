const STATUS_COLORS: Record<string, string> = {
    archived: 'bg-green-100 text-green-700 dark:bg-green-900/20 dark:text-green-400',
    supervisor_approved: 'bg-blue-100 text-blue-700 dark:bg-blue-900/20 dark:text-blue-400',
    hod_approved: 'bg-blue-100 text-blue-700 dark:bg-blue-900/20 dark:text-blue-400',
    in_progress: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/20 dark:text-indigo-400',
    ready_for_defense: 'bg-purple-100 text-purple-700 dark:bg-purple-900/20 dark:text-purple-400',
    under_defense: 'bg-purple-100 text-purple-700 dark:bg-purple-900/20 dark:text-purple-400',
    revisions_required: 'bg-orange-100 text-orange-700 dark:bg-orange-900/20 dark:text-orange-400',
    rejected: 'bg-red-100 text-red-700 dark:bg-red-900/20 dark:text-red-400',
    cancelled: 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
};

const STATUS_LABELS: Record<string, string> = {
    archived: 'منجز',
    supervisor_approved: 'موافقة المشرف',
    hod_approved: 'موافقة رئيس القسم',
    in_progress: 'تحت التنفيذ',
    ready_for_defense: 'جاهز للمناقشة',
    under_defense: 'تحت المناقشة',
    revisions_required: 'يحتاج تعديلات',
    rejected: 'مرفوض',
    cancelled: 'ملغي',
};

export function statusColor(name: string): string {
    return STATUS_COLORS[name] ?? 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400';
}

export function statusLabel(name: string): string {
    return STATUS_LABELS[name] ?? name;
}
