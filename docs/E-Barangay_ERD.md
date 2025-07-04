# E-Barangay Portal System - Entity Relationship Diagram (ERD)

## Database Schema Overview

Based on your current implementation, here's the comprehensive ERD for the E-Barangay Portal System:

```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                           E-BARANGAY PORTAL SYSTEM ERD                             │
└─────────────────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────┐    ┌─────────────────────────┐    ┌─────────────────────────┐
│       RESIDENTS         │    │    ADMIN_USERS          │    │   DOCUMENT_UPLOADS      │
├─────────────────────────┤    ├─────────────────────────┤    ├─────────────────────────┤
│ 🔑 id (PK)             │    │ 🔑 id (PK)             │    │ 🔑 id (PK)             │
│    email (UNIQUE)       │    │    email (UNIQUE)       │    │ 🔗 resident_id (FK)    │
│    password             │    │    password             │    │    document_type        │
│    first_name           │    │    first_name           │    │    file_name            │
│    last_name            │    │    last_name            │    │    file_path            │
│    middle_name          │    │    role                 │    │    file_size            │
│    sex                  │    │    status               │    │    uploaded_at          │
│    birth_date           │    │    last_login           │    └─────────────────────────┘
│    age                  │    │    created_at           │              │
│    civil_status         │    │    updated_at           │              │
│    citizenship          │    │    created_by           │              │
│    profile_picture      │    └─────────────────────────┘              │
│    house_no             │                                             │
│    lot                  │                                             │
│    street               │    ┌─────────────────────────┐              │
│    purok                │    │      REQUESTS           │              │
│    barangay             │    ├─────────────────────────┤              │
│    city                 │    │ 🔑 id (PK)             │              │
│    province             │    │ 🔗 resident_id (FK)    │◄─────────────┘
│    zip_code             │    │    type                 │
│    years_of_residency   │    │    purpose              │
│    mobile_number        │    │    status               │
│    landline_number      │    │    request_details      │
│    voter_status         │    │    processing_fee       │
│    voter_id             │    │    document_path        │
│    valid_id_type        │    │    can_download         │
│    valid_id_number      │    │    can_reupload         │
│    barangay_id_number   │    │    admin_notes          │
│    cedula_number        │    │    created_at           │
│    emergency_contact_*  │    │    updated_at           │
│    employment_status    │    │    processed_at         │
│    occupation           │    └─────────────────────────┘
│    place_of_work        │              │
│    monthly_income_range │              │
│    role                 │              │
│    status               │              │
│    created_at           │              │
│    updated_at           │              │
└─────────────────────────┘              │
           │                             │
           │                             │
           ▼                             ▼
┌─────────────────────────┐    ┌─────────────────────────┐
│    REQUEST_TYPES        │    │   BLOTTER_REPORTS       │
├─────────────────────────┤    ├─────────────────────────┤
│ 🔑 id (PK)             │    │ 🔑 id (PK)             │
│    name                 │    │ 🔗 complainant_id (FK) │
│    description          │    │    incident_type        │
│    required_fields      │    │    incident_date        │
│    processing_fee       │    │    incident_time        │
│    processing_days      │    │    location             │
│    is_active            │    │    description          │
│    created_at           │    │    respondent_name      │
└─────────────────────────┘    │    respondent_address   │
                               │    status               │
                               │    admin_notes          │
                               │    created_at           │
                               │    updated_at           │
                               └─────────────────────────┘

┌─────────────────────────┐    ┌─────────────────────────┐
│  ADMIN_ACTIVITY_LOG     │    │  USER_ACTIVITY_LOG      │
├─────────────────────────┤    ├─────────────────────────┤
│ 🔑 id (PK)             │    │ 🔑 id (PK)             │
│ 🔗 admin_id (FK)       │    │ 🔗 user_id (FK)        │
│    action               │    │    user_type            │
│    target_type          │    │    action               │
│    target_id            │    │    target_type          │
│    details              │    │    target_id            │
│    ip_address           │    │    details              │
│    user_agent           │    │    ip_address           │
│    created_at           │    │    user_agent           │
└─────────────────────────┘    │    created_at           │
                               └─────────────────────────┘

┌─────────────────────────┐
│     ANNOUNCEMENTS       │
├─────────────────────────┤
│ 🔑 id (PK)             │
│ 🔗 admin_id (FK)       │
│    title                │
│    content              │
│    announcement_type    │
│    priority             │
│    is_active            │
│    date_posted          │
│    expiry_date          │
│    created_at           │
│    updated_at           │
└─────────────────────────┘

┌─────────────────────────┐
│   BARANGAY_OFFICIALS    │
├─────────────────────────┤
│ 🔑 id (PK)             │
│    name                 │
│    position             │
│    department           │
│    contact_number       │
│    email                │
│    term_start           │
│    term_end             │
│    is_active            │
│    photo_path           │
│    created_at           │
│    updated_at           │
└─────────────────────────┘
```

## Relationships

### Primary Relationships:
1. **RESIDENTS** (1) ←→ (M) **REQUESTS** - One resident can have many requests
2. **RESIDENTS** (1) ←→ (M) **DOCUMENT_UPLOADS** - One resident can upload many documents
3. **RESIDENTS** (1) ←→ (M) **BLOTTER_REPORTS** - One resident can file many blotter reports
4. **RESIDENTS** (1) ←→ (M) **USER_ACTIVITY_LOG** - One resident can have many activity logs
5. **ADMIN_USERS** (1) ←→ (M) **ADMIN_ACTIVITY_LOG** - One admin can have many activity logs
6. **ADMIN_USERS** (1) ←→ (M) **ANNOUNCEMENTS** - One admin can create many announcements

### Key Constraints:
- **RESIDENTS.email** - UNIQUE constraint
- **ADMIN_USERS.email** - UNIQUE constraint
- **RESIDENTS.role** - ENUM('Resident', 'Admin', 'Barangay Official')
- **REQUESTS.status** - ENUM('pending', 'approved', 'rejected', 'ready_for_pickup')
- **BLOTTER_REPORTS.status** - ENUM('filed', 'under_investigation', 'resolved', 'dismissed')

## Data Types and Constraints

### RESIDENTS Table:
- **id**: INT AUTO_INCREMENT PRIMARY KEY
- **email**: VARCHAR(255) UNIQUE NOT NULL
- **password**: VARCHAR(255) NOT NULL
- **role**: ENUM('Resident', 'Admin', 'Barangay Official') DEFAULT 'Resident'
- **status**: ENUM('Active', 'Deactivated', 'Pending Approval') DEFAULT 'Active'

### REQUESTS Table:
- **id**: INT AUTO_INCREMENT PRIMARY KEY
- **resident_id**: INT NOT NULL (FK to RESIDENTS.id)
- **type**: VARCHAR(100) NOT NULL
- **status**: ENUM('pending', 'approved', 'rejected', 'ready_for_pickup') DEFAULT 'pending'
- **processing_fee**: DECIMAL(10,2) DEFAULT 0.00

### ACTIVITY LOGS:
- **admin_activity_log**: Tracks all admin actions
- **user_activity_log**: Tracks all resident/user actions
- Both include IP address and user agent for security auditing

## Indexes for Performance:
- **residents**: email, voter_status, valid_id_type
- **requests**: resident_id, status, type
- **activity_logs**: admin_id/user_id, action, created_at
- **document_uploads**: resident_id, document_type