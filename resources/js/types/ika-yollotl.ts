export type Service = {
    id: number;
    name: string;
    description: string;
    duration_minutes: number;
    price: number;
    price_label: string;
};

export type BusinessHour = {
    day_of_week: number;
    day_name: string;
    open_time: string;
    close_time: string;
    slot_interval_minutes: number;
};

export type Business = {
    name: string;
    tagline: string;
    philosophy: string;
    phone: string;
    whatsapp: string;
    email: string;
    address: {
        street: string;
        city: string;
        state: string;
        postal_code: string;
        full: string;
        short: string;
    };
    maps: {
        query: string;
        coordinates: {
            lat: number;
            lng: number;
        };
        embed_url: string;
        directions_url: string;
    };
    hours: {
        days: string;
        open: string;
        close: string;
        note: string;
        closed: string;
    };
};

export type AppointmentStatus =
    | 'pending'
    | 'confirmed'
    | 'rescheduled'
    | 'completed'
    | 'cancelled';

export type Slot = {
    start_time: string;
    end_time: string;
    available: boolean;
};

export type Availability = {
    date: string;
    is_open: boolean;
    slots: Slot[];
    service?: Service;
};

export type AppointmentService = {
    id: number;
    name: string;
    description: string;
    duration_minutes: number;
    price: number;
    price_label: string;
};

export type Appointment = {
    id: number;
    patient: {
        name: string;
        phone: string;
        phone_digits: string;
        email: string | null;
    };
    service: AppointmentService;
    date: string;
    start_time: string;
    end_time: string;
    status: AppointmentStatus;
    status_label: string;
    notes: string | null;
    created_at: string | null;
    whatsapp: {
        chat: string;
        confirmation: string;
        reschedule: string;
        reminder: string;
        cancellation: string;
    };
};

export type AgendaFilters = {
    search: string | null;
    status: AppointmentStatus | null;
    from: string | null;
    to: string | null;
    month: string;
};

export type AgendaStats = {
    today: number;
    pending: number;
    month: number;
    cancelled: number;
};

export type DashboardStats = {
    today: number;
    pending: number;
    month: number;
    services: number;
};

export type AgendaCounts = Record<AppointmentStatus, number>;

export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    links: PaginationLink[];
};

export type CalendarDay = {
    key: string;
    day: number;
    weekday: number;
    inMonth: boolean;
    isPast: boolean;
    isToday: boolean;
};
