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

export const fakeMonthlyRevenue = [
    { month: 'Jan', amount: 3200 },
    { month: 'Fév', amount: 4100 },
    { month: 'Mar', amount: 2800 },
    { month: 'Avr', amount: 5600 },
    { month: 'Mai', amount: 6800 },
    { month: 'Juin', amount: 1900 },
];

export const fakeDocuments = [
    {
        id: 1,
        name: 'Contrat de prestation',
        type: 'Contrat',
        updatedAt: '2026-05-28',
        content: `<h2>Contrat de prestation de services</h2>
<p>Entre <strong>Studio MT</strong>, auto-entrepreneur, SIRET 123 456 789 00012, ci-après désigné « le Prestataire »,</p>
<p>Et la société <strong>[Nom du client]</strong>, ci-après désignée « le Client »,</p>
<h3>Article 1 — Objet</h3>
<p>Le Prestataire s'engage à réaliser les prestations suivantes pour le compte du Client :</p>
<ul><li>Conception graphique et identité visuelle</li><li>Développement web front-end</li><li>Suivi et maintenance</li></ul>
<h3>Article 2 — Durée</h3>
<p>La prestation débutera le <strong>[date de début]</strong> pour une durée de <strong>[X] semaines</strong>.</p>
<h3>Article 3 — Rémunération</h3>
<p>Le Client s'engage à régler la somme de <strong>[montant] € HT</strong> selon les modalités définies dans le devis joint. Un acompte de 30 % est dû à la commande, le solde à la livraison.</p>
<h3>Article 4 — Propriété intellectuelle</h3>
<p>Les droits de propriété intellectuelle sont transférés au Client après règlement intégral de la facture finale.</p>`,
    },
    {
        id: 2,
        name: 'Proposition commerciale',
        type: 'Proposition',
        updatedAt: '2026-05-15',
        content: `<h2>Proposition commerciale</h2>
<p>Bonjour,</p>
<p>Suite à notre échange, je vous soumets cette proposition pour la réalisation de votre projet digital.</p>
<h3>Votre besoin</h3>
<p>Vous souhaitez créer une présence en ligne solide avec un site web moderne et performant, reflet de l'image de votre entreprise.</p>
<h3>Notre approche</h3>
<ul><li><strong>Audit et cadrage</strong> — Analyse des besoins et définition des objectifs</li><li><strong>Design UI/UX</strong> — Maquettes sur-mesure et prototypes interactifs</li><li><strong>Développement</strong> — Intégration responsive, optimisée SEO</li><li><strong>Livraison et formation</strong> — Mise en production et prise en main</li></ul>
<h3>Budget estimatif</h3>
<p>La réalisation est estimée entre <strong>4 500 € HT</strong> et <strong>6 500 € HT</strong> selon les options retenues. Délai de réalisation : 6 à 8 semaines.</p>`,
    },
    {
        id: 3,
        name: 'CGV — Conditions générales de vente',
        type: 'Document légal',
        updatedAt: '2026-04-10',
        content: `<h2>Conditions générales de vente</h2>
<p><em>En vigueur au 1er janvier 2026</em></p>
<h3>1. Objet et champ d'application</h3>
<p>Les présentes CGV s'appliquent à toutes les prestations de services réalisées par Studio MT auprès de ses clients professionnels.</p>
<h3>2. Devis et commandes</h3>
<p>Tout devis signé vaut bon de commande et engage les deux parties. Les devis sont valables <strong>30 jours</strong> à compter de leur date d'émission.</p>
<h3>3. Paiement</h3>
<p>Les factures sont payables à 30 jours date de facture. Un acompte de 30 % est demandé à la commande. En cas de retard de paiement, des pénalités de 3× le taux légal s'appliquent de plein droit.</p>
<h3>4. Propriété intellectuelle</h3>
<p>Les droits de propriété intellectuelle sur les livrables sont transférés au Client après règlement intégral des factures.</p>`,
    },
    {
        id: 4,
        name: 'Cahier des charges type',
        type: 'Template',
        updatedAt: '2026-03-22',
        content: `<h2>Cahier des charges — Projet web</h2>
<h3>1. Présentation du projet</h3>
<p>[Description du projet, contexte, enjeux stratégiques]</p>
<h3>2. Objectifs</h3>
<ul><li>Objectif principal : [à compléter]</li><li>Objectifs secondaires : [à compléter]</li></ul>
<h3>3. Public cible</h3>
<p>[Description de la cible, personas, comportements attendus]</p>
<h3>4. Fonctionnalités attendues</h3>
<ul><li>Page d'accueil avec présentation</li><li>Section services / portfolio</li><li>Formulaire de contact sécurisé</li><li>Blog / actualités (optionnel)</li></ul>
<h3>5. Contraintes techniques</h3>
<p>CMS souhaité : [WordPress / headless / autre] — Hébergement : [OVH / Netlify / autre]</p>
<h3>6. Budget et délais</h3>
<p>Budget alloué : <strong>[X] € HT</strong> — Mise en ligne souhaitée avant le <strong>[date]</strong></p>`,
    },
];
