import type { LucideIcon } from 'lucide-vue-next';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon;
    isActive?: boolean;
}

export interface SimilarProject {
    type: 'project' | 'proposal';
    id: number;
    title: string;
    academic_year: string | null;
    department: string | null;
    similarity_percent: number;
    url: string | null;
}

export interface Department {
    id: number;
    name: string;
    code?: string;
}

export interface AcademicDegree {
    id: number;
    degree_name: string;
    degree_code: string;
}

export interface FacultyMember {
    id: number;
    full_name: string;
    phone_number: string;
    email: string;
    degree_id: number;
    user_id: number | null;
    degree?: AcademicDegree;
    departments?: Department[];
}

export interface Semester {
    id: number;
    name: string;
    sort_order: number;
    is_active: boolean;
}

export interface SystemSettings {
    max_students_per_project: number;
    examiners_per_project: number;
    max_projects_per_supervisor_per_semester: number;
    academic_year_format: '2_digit' | '4_digit';
}

export interface NotificationItem {
    id: string;
    title: string | null;
    message: string | null;
    url: string | null;
    read_at: string | null;
    created_at: string;
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    notifications: {
        unreadCount: number;
        recent: NotificationItem[];
    };
    systemSettings: SystemSettings | null;
    semesters: string[];
    ziggy: {
        location: string;
        url: string;
        port: null | number;
        defaults: Record<string, unknown>;
        routes: Record<string, string>;
    };
    flash?: {
        success?: string;
        error?: string;
        similarity_warning?: SimilarProject[];
    };
    [key: string]: unknown;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    role?: string;
    department_id?: number | null;
}

export type BreadcrumbItemType = BreadcrumbItem;
