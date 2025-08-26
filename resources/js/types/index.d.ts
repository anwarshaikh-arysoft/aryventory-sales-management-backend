import { LucideIcon } from 'lucide-react';
import type { Config } from 'ziggy-js';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon | null;
    isActive?: boolean;
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    ziggy: Config & { location: string };
    sidebarOpen: boolean;
    [key: string]: unknown;
}

export interface User {
    id: number;
    name: string;
    email: string;
    phone?: string;
    designation?: string;
    role_id?: number;
    group_id?: number;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    role?: Role;
    group?: Group;
    [key: string]: unknown; // This allows for additional properties...
}

export interface Role {
    id: number;
    name: string;
    created_at: string;
    updated_at: string;
}

export interface Group {
    id: number;
    name: string;
    created_at: string;
    updated_at: string;
}

export interface PaginatedResponse<T> {
    data: T[];
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
    from: number;
    to: number;
    prev_page_url: string | null;
    next_page_url: string | null;
}

export interface Lead {
    id: number;
    created_by?: number;
    assigned_to?: number;
    last_updated_by?: number;
    shop_name: string;
    contact_person: string;
    mobile_number: string;
    alternate_number?: string;
    email?: string;
    address?: string;
    area_locality?: string;
    pincode?: string;
    gps_location?: string;
    business_type?: number;
    current_system?: number;
    lead_status?: number;
    plan_interest?: string;
    next_follow_up_date?: string;
    meeting_notes?: string;
    created_at: string;
    updated_at: string;
    created_by_user?: User;
    assigned_to_user?: User;
    last_updated_by_user?: User;
    business_type_data?: BusinessType;
    current_system_data?: CurrentSystem;
    lead_status_data?: LeadStatus;
    histories?: LeadHistory[];
    meetings?: Meeting[];
}

export interface BusinessType {
    id: number;
    name: string;
    created_at: string;
    updated_at: string;
}

export interface MonthlySalesVolume {
    id: number;
    volume: string;
    created_at: string;
    updated_at: string;
}

export interface CurrentSystem {
    id: number;
    name: string;
    created_at: string;
    updated_at: string;
}

export interface LeadStatus {
    id: number;
    name: string;
    created_at: string;
    updated_at: string;
}

export interface LeadHistory {
    id?: number;
    lead_id?: number;
    updated_by?: number;
    status_before?: string | number | null;
    status_after?: string | number | null;
    timestamp?: string; // ISO datetime string
    notes?: string | null;
    updated_by_user?: User; // Some APIs might hydrate this name
    updated_by?: User; // Laravel relation 'updatedBy' serializes as 'updated_by'
    action?: string | number | null;
}

export interface LeadFormOptions {
    business_types: BusinessType[];
    current_systems: CurrentSystem[];
    lead_statuses: LeadStatus[];
    users: User[];
}
