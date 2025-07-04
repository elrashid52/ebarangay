# E-Barangay Portal System - Hierarchical Flowchart

## System Architecture Overview

```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                        E-BARANGAY PORTAL SYSTEM                                    │
│                           (Excluding Login/Registration)                           │
└─────────────────────────────────────────────────────────────────────────────────────┘
                                        │
                                        ▼
                        ┌───────────────────────────────┐
                        │        USER ROLES             │
                        └───────────────────────────────┘
                                        │
                        ┌───────────────┴───────────────┐
                        ▼                               ▼
            ┌─────────────────────┐           ┌─────────────────────┐
            │   ADMIN DASHBOARD   │           │ RESIDENT DASHBOARD  │
            └─────────────────────┘           └─────────────────────┘
                        │                               │
                        ▼                               ▼

┌─────────────────────────────────────────┐   ┌─────────────────────────────────────────┐
│            ADMIN MODULES                │   │           RESIDENT MODULES              │
├─────────────────────────────────────────┤   ├─────────────────────────────────────────┤
│                                         │   │                                         │
│ ┌─────────────────────────────────────┐ │   │ ┌─────────────────────────────────────┐ │
│ │        MANAGE RESIDENTS             │ │   │ │       VIEW ANNOUNCEMENTS            │ │
│ ├─────────────────────────────────────┤ │   │ ├─────────────────────────────────────┤ │
│ │ • View Resident Records             │ │   │ │ • Browse All Announcements          │ │
│ │ • Add New Resident                  │ │   │ │ • Filter by Type/Priority           │ │
│ │ • Edit Resident Information         │ │   │ │ • View Announcement Details         │ │
│ │ • Delete/Deactivate Resident        │ │   │ │ • Search Announcements              │ │
│ │ • Search & Filter Residents         │ │   │ └─────────────────────────────────────┘ │
│ │ • Export Resident Data              │ │   │                                         │
│ └─────────────────────────────────────┘ │   │ ┌─────────────────────────────────────┐ │
│                                         │   │ │     VIEW BARANGAY OFFICIALS         │ │
│ ┌─────────────────────────────────────┐ │   │ ├─────────────────────────────────────┤ │
│ │     MANAGE DOCUMENT REQUESTS        │ │   │ │ • View Officials Directory          │ │
│ ├─────────────────────────────────────┤ │   │ │ • Contact Information               │ │
│ │ • View All Requests                 │ │   │ │ • Department Structure              │ │
│ │ • Filter by Status/Type             │ │   │ │ • Official Terms & Positions       │ │
│ │ • Review Request Details            │ │   │ └─────────────────────────────────────┘ │
│ │ • Approve Request                   │ │   │                                         │
│ │ • Reject Request (with reason)      │ │   │ ┌─────────────────────────────────────┐ │
│ │ • Upload Certificate Documents      │ │   │ │       REQUEST DOCUMENTS             │ │
│ │ • Generate Reports                  │ │   │ ├─────────────────────────────────────┤ │
│ └─────────────────────────────────────┘ │   │ │ • Barangay Clearance                │ │
│                                         │   │ │ • Certificate of Residency          │ │
│ ┌─────────────────────────────────────┐ │   │ │ • Certificate of Indigency          │ │
│ │      MANAGE ANNOUNCEMENTS           │ │   │ │ • Business Permit                   │ │
│ ├─────────────────────────────────────┤ │   │ │ • Barangay ID                       │ │
│ │ • Create New Announcement           │ │   │ │ • Upload Required Documents         │ │
│ │ • Edit Existing Announcements       │ │   │ │ • Select Payment Method             │ │
│ │ • Delete Announcements              │ │   │ │ • Submit Request                    │ │
│ │ • Set Priority & Expiry             │ │   │ └─────────────────────────────────────┘ │
│ │ • Publish/Unpublish                 │ │   │                                         │
│ │ • View Announcement Analytics       │ │   │ ┌─────────────────────────────────────┐ │
│ └─────────────────────────────────────┘ │   │ │   TRACK DOCUMENT REQUEST STATUS     │ │
│                                         │   │ ├─────────────────────────────────────┤ │
│ ┌─────────────────────────────────────┐ │   │ │ • View All My Requests              │ │
│ │     MANAGE BARANGAY OFFICIALS       │ │   │ │ • Check Request Status              │ │
│ ├─────────────────────────────────────┤ │   │ │ • Download Approved Documents       │ │
│ │ • Add New Official                  │ │   │ │ • View Rejection Reasons            │ │
│ │ • Edit Official Information         │ │   │ │ • Resubmit Rejected Requests        │ │
│ │ • Remove/Deactivate Official        │ │   │ │ • Filter by Status/Type             │ │
│ │ • Manage Terms & Positions          │ │   │ │ • Request History                   │ │
│ │ • Upload Official Photos            │ │   │ └─────────────────────────────────────┘ │
│ │ • Set Contact Information           │ │   │                                         │
│ └─────────────────────────────────────┘ │   │ ┌─────────────────────────────────────┐ │
│                                         │   │ │        PROFILE MANAGEMENT           │ │
│ ┌─────────────────────────────────────┐ │   │ ├─────────────────────────────────────┤ │
│ │       REPORT ACTIVITIES             │ │   │ │ • Update Personal Information       │ │
│ ├─────────────────────────────────────┤ │   │ │ • Change Password                   │ │
│ │ • View System Activity Logs         │ │   │ │ • Upload Profile Picture            │ │
│ │ • Filter by Date/User/Action        │ │   │ │ • Update Contact Details            │ │
│ │ • Generate Activity Reports         │ │   │ │ • Emergency Contact Info            │ │
│ │ • Print Professional Reports        │ │   │ │ • Employment Information            │ │
│ │ • Monitor Admin & User Actions      │ │   │ │ • Address Information               │ │
│ │ • Export Activity Data              │ │   │ └─────────────────────────────────────┘ │
│ └─────────────────────────────────────┘ │   └─────────────────────────────────────────┘
└─────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────────────────┐
│                           SYSTEM DATA MANAGEMENT                                   │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                     │
│ ┌─────────────────────┐  ┌─────────────────────┐  ┌─────────────────────┐         │
│ │    RESIDENTS        │  │    ADMIN_USERS      │  │   DOCUMENT_UPLOADS  │         │
│ │ ─────────────────── │  │ ─────────────────── │  │ ─────────────────── │         │
│ │ • id (PK)           │  │ • id (PK)           │  │ • id (PK)           │         │
│ │ • email (UNIQUE)    │  │ • email (UNIQUE)    │  │ • resident_id (FK)  │         │
│ │ • password          │  │ • password          │  │ • document_type     │         │
│ │ • first_name        │  │ • first_name        │  │ • file_name         │         │
│ │ • last_name         │  │ • last_name         │  │ • file_path         │         │
│ │ • middle_name       │  │ • role              │  │ • file_size         │         │
│ │ • personal_info     │  │ • status            │  │ • uploaded_at       │         │
│ │ • address_info      │  │ • created_at        │  └─────────────────────┘         │
│ │ • contact_info      │  │ • updated_at        │                                  │
│ │ • employment_info   │  └─────────────────────┘                                  │
│ │ • role              │                                                           │
│ │ • status            │  ┌─────────────────────┐  ┌─────────────────────┐         │
│ │ • created_at        │  │     REQUESTS        │  │   REQUEST_TYPES     │         │
│ │ • updated_at        │  │ ─────────────────── │  │ ─────────────────── │         │
│ └─────────────────────┘  │ • id (PK)           │  │ • id (PK)           │         │
│                          │ • resident_id (FK)  │  │ • name              │         │
│ ┌─────────────────────┐  │ • type              │  │ • description       │         │
│ │  BLOTTER_REPORTS    │  │ • purpose           │  │ • required_fields   │         │
│ │ ─────────────────── │  │ • status            │  │ • processing_fee    │         │
│ │ • id (PK)           │  │ • request_details   │  │ • processing_days   │         │
│ │ • complainant_id(FK)│  │ • processing_fee    │  │ • is_active         │         │
│ │ • incident_type     │  │ • document_path     │  └─────────────────────┘         │
│ │ • incident_date     │  │ • can_download      │                                  │
│ │ • incident_time     │  │ • can_reupload      │  ┌─────────────────────┐         │
│ │ • location          │  │ • admin_notes       │  │   ANNOUNCEMENTS     │         │
│ │ • description       │  │ • created_at        │  │ ─────────────────── │         │
│ │ • respondent_name   │  │ • updated_at        │  │ • id (PK)           │         │
│ │ • respondent_addr   │  │ • processed_at      │  │ • admin_id (FK)     │         │
│ │ • status            │  └─────────────────────┘  │ • title             │         │
│ │ • admin_notes       │                           │ • content           │         │
│ │ • created_at        │  ┌─────────────────────┐  │ • announcement_type │         │
│ │ • updated_at        │  │ BARANGAY_OFFICIALS  │  │ • priority          │         │
│ └─────────────────────┘  │ ─────────────────── │  │ • is_active         │         │
│                          │ • id (PK)           │  │ • date_posted       │         │
│ ┌─────────────────────┐  │ • name              │  │ • expiry_date       │         │
│ │ ADMIN_ACTIVITY_LOG  │  │ • position          │  │ • created_at        │         │
│ │ ─────────────────── │  │ • department        │  │ • updated_at        │         │
│ │ • id (PK)           │  │ • contact_number    │  └─────────────────────┘         │
│ │ • admin_id (FK)     │  │ • email             │                                  │
│ │ • action            │  │ • term_start        │  ┌─────────────────────┐         │
│ │ • target_type       │  │ • term_end          │  │ USER_ACTIVITY_LOG   │         │
│ │ • target_id         │  │ • is_active         │  │ ─────────────────── │         │
│ │ • details           │  │ • photo_path        │  │ • id (PK)           │         │
│ │ • ip_address        │  │ • created_at        │  │ • user_id (FK)      │         │
│ │ • user_agent        │  │ • updated_at        │  │ • user_type         │         │
│ │ • created_at        │  └─────────────────────┘  │ • action            │         │
│ └─────────────────────┘                           │ • target_type       │         │
│                                                   │ • target_id         │         │
│                                                   │ • details           │         │
│                                                   │ • ip_address        │         │
│                                                   │ • user_agent        │         │
│                                                   │ • created_at        │         │
│                                                   └─────────────────────┘         │
└─────────────────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────────────────┐
│                              SYSTEM WORKFLOW                                       │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                     │
│  ADMIN WORKFLOW:                          RESIDENT WORKFLOW:                       │
│  ┌─────────────────┐                      ┌─────────────────┐                     │
│  │ Login to Admin  │                      │ Login to Portal │                     │
│  │ Dashboard       │                      │                 │                     │
│  └─────────────────┘                      └─────────────────┘                     │
│           │                                        │                              │
│           ▼                                        ▼                              │
│  ┌─────────────────┐                      ┌─────────────────┐                     │
│  │ Select Module:  │                      │ Select Action:  │                     │
│  │ • Residents     │                      │ • View Info     │                     │
│  │ • Requests      │                      │ • Request Docs  │                     │
│  │ • Announcements │                      │ • Track Status  │                     │
│  │ • Officials     │                      │ • Update Profile│                     │
│  │ • Activities    │                      └─────────────────┘                     │
│  └─────────────────┘                               │                              │
│           │                                        ▼                              │
│           ▼                              ┌─────────────────┐                     │
│  ┌─────────────────┐                     │ Perform Action  │                     │
│  │ Perform Admin   │                     │ & Submit Data   │                     │
│  │ Actions         │                     └─────────────────┘                     │
│  └─────────────────┘                              │                              │
│           │                                       ▼                              │
│           ▼                              ┌─────────────────┐                     │
│  ┌─────────────────┐                     │ System Updates  │                     │
│  │ System Logs     │                     │ Database &      │                     │
│  │ Activity        │                     │ Logs Activity   │                     │
│  └─────────────────┘                     └─────────────────┘                     │
│                                                                                   │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

## Module Descriptions

### Admin Dashboard Modules:

1. **Manage Residents**
   - Complete CRUD operations for resident records
   - Advanced search and filtering capabilities
   - Bulk operations and data export

2. **Manage Document Requests**
   - Comprehensive request review system
   - Document upload and approval workflow
   - Status tracking and notifications

3. **Manage Announcements**
   - Content management system for announcements
   - Priority and expiry date management
   - Analytics and engagement tracking

4. **Manage Barangay Officials**
   - Official directory management
   - Term and position tracking
   - Contact information management

5. **Report Activities**
   - System activity monitoring
   - Professional report generation
   - Security audit trails

### Resident Dashboard Modules:

1. **View Announcements**
   - Browse and search announcements
   - Filter by relevance and date
   - Detailed announcement viewing

2. **View Barangay Officials**
   - Official directory access
   - Contact information display
   - Department structure viewing

3. **Request Documents**
   - Multi-step document request process
   - Document upload and validation
   - Payment method selection

4. **Track Request Status**
   - Real-time status monitoring
   - Document download capabilities
   - Request history management

5. **Profile Management**
   - Comprehensive profile editing
   - Security settings management
   - Document and photo uploads

## Data Flow Architecture:

The system follows a structured data flow where:
- **User actions** trigger **database operations**
- **Activity logging** captures all system interactions
- **Status updates** provide real-time feedback
- **Document management** handles file operations
- **Notification system** keeps users informed