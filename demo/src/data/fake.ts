export const fakeUser = {
    name: 'Maxime Tourneur',
    email: 'demo@myoffice.app',
};

export const fakeCompany = {
    name: 'Studio MT',
    address: '12 rue de la Paix, 75001 Paris',
    vat: 'FR12345678901',
    iban: 'FR76 1234 5678 9012 3456 7890 123',
    siret: '123 456 789 00012',
};

export const fakeStats = {
    clients: 14,
    prospects: 8,
    quotes_pending: 3,
    invoices_unpaid: 2,
    revenue_month: 6800,
    revenue_year: 54200,
};

export const fakeClients = [
    { id: 1, name: 'Agence Pixel', email: 'contact@agencepixel.fr', phone: '+33 6 12 34 56 78', city: 'Paris', invoices_count: 6, total_revenue: 18400 },
    { id: 2, name: 'Startup NovaTech', email: 'hello@novatech.io', phone: '+33 6 98 76 54 32', city: 'Lyon', invoices_count: 3, total_revenue: 9200 },
    { id: 3, name: 'Cabinet RH Conseil', email: 'info@rhconseil.fr', phone: '+33 1 44 55 66 77', city: 'Bordeaux', invoices_count: 4, total_revenue: 12600 },
    { id: 4, name: 'E-Shop Lumière', email: 'ceo@eshoplumiere.com', phone: '+33 6 23 45 67 89', city: 'Nantes', invoices_count: 2, total_revenue: 4800 },
    { id: 5, name: 'BTP Horizon', email: 'direction@btphorizon.fr', phone: '+33 4 89 23 11 00', city: 'Marseille', invoices_count: 1, total_revenue: 3200 },
];

export const fakeProspects = [
    { id: 1, name: 'Media Group Sud', email: 'contact@mediasud.fr', status: 'En cours', source: 'Recommandation', created_at: '2026-05-10' },
    { id: 2, name: 'Fintech Clarity', email: 'dev@clarity.io', status: 'À relancer', source: 'LinkedIn', created_at: '2026-04-28' },
    { id: 3, name: 'Association Art & Vie', email: 'info@artevie.org', status: 'Nouveau', source: 'Site web', created_at: '2026-05-30' },
];

export const fakeInvoices = [
    { id: 1, number: 'FAC-2026-014', client: 'Agence Pixel', amount: 2400, status: 'paid', date: '2026-05-20', due_date: '2026-06-19' },
    { id: 2, number: 'FAC-2026-013', client: 'Startup NovaTech', amount: 3800, status: 'sent', date: '2026-05-12', due_date: '2026-06-11' },
    { id: 3, number: 'FAC-2026-012', client: 'Cabinet RH Conseil', amount: 1650, status: 'overdue', date: '2026-04-15', due_date: '2026-05-15' },
    { id: 4, number: 'FAC-2026-011', client: 'E-Shop Lumière', amount: 4200, status: 'paid', date: '2026-04-02', due_date: '2026-05-02' },
    { id: 5, number: 'FAC-2026-010', client: 'BTP Horizon', amount: 900, status: 'draft', date: '2026-05-28', due_date: '2026-06-27' },
    { id: 6, number: 'FAC-2026-009', client: 'Agence Pixel', amount: 1800, status: 'paid', date: '2026-03-18', due_date: '2026-04-17' },
];

export const fakeQuotes = [
    { id: 1, number: 'DEV-2026-007', client: 'Media Group Sud', amount: 5500, status: 'sent', date: '2026-05-25' },
    { id: 2, number: 'DEV-2026-006', client: 'Fintech Clarity', amount: 8200, status: 'draft', date: '2026-05-18' },
    { id: 3, number: 'DEV-2026-005', client: 'Agence Pixel', amount: 3100, status: 'accepted', date: '2026-05-05' },
];
