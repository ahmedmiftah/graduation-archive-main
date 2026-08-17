const STATUS_COLORS: Record<string, string> = {
    pending: 'bg-blue-100 text-blue-700 dark:bg-blue-900/20 dark:text-blue-400',
    needs_revision: 'bg-orange-100 text-orange-700 dark:bg-orange-900/20 dark:text-orange-400',
    rejected: 'bg-red-100 text-red-700 dark:bg-red-900/20 dark:text-red-400',
    approved: 'bg-green-100 text-green-700 dark:bg-green-900/20 dark:text-green-400',
    superseded: 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
};

const STATUS_LABELS: Record<string, string> = {
    pending: 'مبدئي',
    needs_revision: 'مقبول بشرط التعديل',
    rejected: 'مرفوض نهائياً',
    approved: 'معتمد',
    superseded: 'مستبدَل',
};

export function proposalStatusColor(status: string): string {
    return STATUS_COLORS[status] ?? 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400';
}

export function proposalStatusLabel(status: string): string {
    return STATUS_LABELS[status] ?? status;
}
