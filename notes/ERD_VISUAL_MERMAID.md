# STUDIFY - ERD Visual (Mermaid Diagram)

## 📊 Entity Relationship Diagram (ERD)

Diagram ini dapat di-render di GitHub, VS Code (dengan extension Mermaid), atau di https://mermaid.live

```mermaid
erDiagram
    USERS ||--o{ CLASSROOMS : "owns (owner_id)"
    USERS ||--o{ CLASSROOM_USER : "joins"
    CLASSROOMS ||--o{ CLASSROOM_USER : "has members"
    CLASSROOMS ||--o{ CLASS_SCHEDULES : "has schedules"
    USERS ||--o{ PERSONAL_SCHEDULES : "creates"
    USERS ||--o{ CLASS_SCHEDULES : "coordinates_1"
    USERS ||--o{ CLASS_SCHEDULES : "coordinates_2"
    USERS ||--o{ DEVICE_TOKENS : "has tokens"
    USERS ||--o{ NOTIFICATIONS : "receives"
    CLASS_SCHEDULES ||--o{ REMINDERS : "has reminders (polymorphic)"
    PERSONAL_SCHEDULES ||--o{ REMINDERS : "has reminders (polymorphic)"

    USERS {
        bigint id PK
        string name
        string email UK "UNIQUE"
        timestamp email_verified_at
        string password
        string remember_token
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at "SOFT DELETE"
    }

    CLASSROOMS {
        bigint id PK
        bigint owner_id FK "→ users.id ON DELETE RESTRICT"
        string name
        string unique_code UK "UNIQUE for joining"
        text description
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at "SOFT DELETE"
    }

    CLASSROOM_USER {
        bigint id PK
        bigint classroom_id FK "→ classrooms.id ON DELETE CASCADE"
        bigint user_id FK "→ users.id ON DELETE CASCADE"
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at "SOFT DELETE"
    }

    CLASS_SCHEDULES {
        bigint id PK
        bigint classroom_id FK "→ classrooms.id ON DELETE CASCADE"
        bigint coordinator_1 FK "→ users.id ON DELETE SET NULL"
        bigint coordinator_2 FK "→ users.id ON DELETE SET NULL"
        string title
        datetime start_time
        datetime end_time
        string location
        string lecturer
        text description
        string color "default: #3B82F6"
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at "SOFT DELETE"
    }

    PERSONAL_SCHEDULES {
        bigint id PK
        bigint user_id FK "→ users.id ON DELETE CASCADE"
        string title
        datetime start_time
        datetime end_time
        string location
        text description
        string color "default: #10B981"
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at "SOFT DELETE"
    }

    REMINDERS {
        bigint id PK
        string remindable_type "ClassSchedule or PersonalSchedule"
        bigint remindable_id "ID of schedule"
        integer minutes_before_start "default: 30"
        enum status "pending, sent, failed"
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at "SOFT DELETE"
    }

    DEVICE_TOKENS {
        bigint id PK
        bigint user_id FK "→ users.id ON DELETE CASCADE"
        string token UK "UNIQUE - FCM/APNS token"
        string platform "iOS/Android"
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at "SOFT DELETE"
    }

    NOTIFICATIONS {
        bigint id PK
        bigint user_id FK "→ users.id ON DELETE CASCADE"
        string title
        text body
        json data "Additional payload"
        boolean is_read "default: false"
        timestamp created_at
        timestamp updated_at
    }
```

---

## 🔄 Data Flow Diagram (DFD) - Level 0

```mermaid
graph TB
    subgraph External["External Entities"]
        USER[👤 User<br/>Mobile App]
        FIREBASE[🔔 Firebase<br/>Cloud Messaging]
    end

    subgraph System["STUDIFY SYSTEM"]
        API[🔧 Laravel API<br/>Backend Server]
        DB[(💾 Database<br/>PostgreSQL/MySQL)]
    end

    USER -->|Register/Login<br/>CRUD Operations| API
    API -->|JWT Token<br/>JSON Response| USER
    API <-->|SQL Queries<br/>Store/Retrieve Data| DB
    API -->|Send Push<br/>Notifications| FIREBASE
    FIREBASE -->|Deliver<br/>Notifications| USER

    style USER fill:#e3f2fd
    style FIREBASE fill:#fff3e0
    style API fill:#f3e5f5
    style DB fill:#e8f5e9
```

---

## 🔄 Data Flow Diagram (DFD) - Level 1

```mermaid
graph TB
    USER[👤 User]
    
    subgraph AUTH["1.0 AUTHENTICATION"]
        LOGIN[1.1 Login/Register]
        TOKEN[1.2 Generate JWT]
        DEVICE[1.3 Store Device Token]
    end
    
    subgraph CLASSROOM["2.0 CLASSROOM MANAGEMENT"]
        CREATE_CLASS[2.1 Create Classroom]
        JOIN_CLASS[2.2 Join Classroom]
        MANAGE_CLASS[2.3 Manage Members]
    end
    
    subgraph SCHEDULE["3.0 SCHEDULE MANAGEMENT"]
        CLASS_SCHED[3.1 Class Schedules]
        PERSONAL_SCHED[3.2 Personal Schedules]
        COMBINED[3.3 Combined View]
    end
    
    subgraph REMINDER["4.0 REMINDER & NOTIFICATION"]
        CREATE_REM[4.1 Create Reminder]
        PROCESS_REM[4.2 Process Reminders]
        SEND_NOTIF[4.3 Send Notification]
    end
    
    DB_USERS[(D1: users)]
    DB_TOKENS[(D2: device_tokens)]
    DB_CLASSROOMS[(D3: classrooms)]
    DB_CLASS_USER[(D4: classroom_user)]
    DB_CLASS_SCHED[(D5: class_schedules)]
    DB_PERSONAL[(D6: personal_schedules)]
    DB_REMINDERS[(D7: reminders)]
    DB_NOTIF[(D8: notifications)]
    FCM[🔔 Firebase]

    USER --> LOGIN
    LOGIN <--> DB_USERS
    LOGIN --> TOKEN
    TOKEN --> DEVICE
    DEVICE <--> DB_TOKENS

    USER --> CREATE_CLASS
    CREATE_CLASS <--> DB_CLASSROOMS
    CREATE_CLASS <--> DB_CLASS_USER
    
    USER --> JOIN_CLASS
    JOIN_CLASS <--> DB_CLASSROOMS
    JOIN_CLASS <--> DB_CLASS_USER
    
    USER --> MANAGE_CLASS
    MANAGE_CLASS <--> DB_CLASS_USER

    USER --> CLASS_SCHED
    CLASS_SCHED <--> DB_CLASS_SCHED
    CLASS_SCHED --> DB_CLASSROOMS
    
    USER --> PERSONAL_SCHED
    PERSONAL_SCHED <--> DB_PERSONAL
    
    USER --> COMBINED
    COMBINED --> DB_CLASS_SCHED
    COMBINED --> DB_PERSONAL

    USER --> CREATE_REM
    CREATE_REM <--> DB_REMINDERS
    
    PROCESS_REM <--> DB_REMINDERS
    PROCESS_REM --> DB_CLASS_SCHED
    PROCESS_REM --> DB_PERSONAL
    PROCESS_REM --> SEND_NOTIF
    
    SEND_NOTIF --> DB_TOKENS
    SEND_NOTIF --> FCM
    SEND_NOTIF <--> DB_NOTIF
    FCM --> USER

    style USER fill:#e3f2fd
    style FCM fill:#fff3e0
```

---

## 🔄 DFD Level 2 - Authentication Flow

```mermaid
graph LR
    USER[👤 User]
    
    subgraph REGISTER["Register Process"]
        REG_INPUT[Input:<br/>name, email, password]
        REG_VALIDATE[Validate<br/>Input]
        REG_HASH[Hash<br/>Password]
        REG_STORE[Store<br/>User]
    end
    
    subgraph LOGIN["Login Process"]
        LOGIN_INPUT[Input:<br/>email, password]
        LOGIN_VERIFY[Verify<br/>Credentials]
        LOGIN_JWT[Generate<br/>JWT Token]
    end
    
    DB_USERS[(D1: users)]
    DB_TOKENS[(D2: device_tokens)]

    USER -->|Register| REG_INPUT
    REG_INPUT --> REG_VALIDATE
    REG_VALIDATE --> REG_HASH
    REG_HASH --> REG_STORE
    REG_STORE --> DB_USERS
    DB_USERS -->|User Data| USER

    USER -->|Login| LOGIN_INPUT
    LOGIN_INPUT --> LOGIN_VERIFY
    LOGIN_VERIFY <--> DB_USERS
    LOGIN_VERIFY --> LOGIN_JWT
    LOGIN_JWT -->|JWT Token| USER
    
    USER -->|Device Token| DB_TOKENS
```

---

## 🔄 DFD Level 2 - Schedule Management Flow

```mermaid
graph TB
    USER[👤 User]
    
    subgraph CLASS_SCHEDULE["Class Schedule Flow"]
        CS_CREATE[Create Class<br/>Schedule]
        CS_VALIDATE[Validate<br/>Membership]
        CS_STORE[Store<br/>Schedule]
    end
    
    subgraph PERSONAL_SCHEDULE["Personal Schedule Flow"]
        PS_CREATE[Create Personal<br/>Schedule]
        PS_STORE[Store<br/>Schedule]
    end
    
    subgraph COMBINED_VIEW["Combined View"]
        CV_FETCH_CLASS[Fetch Class<br/>Schedules]
        CV_FETCH_PERSONAL[Fetch Personal<br/>Schedules]
        CV_MERGE[Merge &<br/>Sort]
    end
    
    DB_CLASSROOMS[(D3: classrooms)]
    DB_CLASS_USER[(D4: classroom_user)]
    DB_CLASS_SCHED[(D5: class_schedules)]
    DB_PERSONAL[(D6: personal_schedules)]

    USER --> CS_CREATE
    CS_CREATE --> CS_VALIDATE
    CS_VALIDATE <--> DB_CLASSROOMS
    CS_VALIDATE <--> DB_CLASS_USER
    CS_VALIDATE --> CS_STORE
    CS_STORE --> DB_CLASS_SCHED

    USER --> PS_CREATE
    PS_CREATE --> PS_STORE
    PS_STORE --> DB_PERSONAL

    USER --> CV_FETCH_CLASS
    CV_FETCH_CLASS --> DB_CLASS_SCHED
    CV_FETCH_CLASS --> CV_MERGE
    
    USER --> CV_FETCH_PERSONAL
    CV_FETCH_PERSONAL --> DB_PERSONAL
    CV_FETCH_PERSONAL --> CV_MERGE
    
    CV_MERGE -->|Combined<br/>Schedules| USER
```

---

## 🔄 DFD Level 2 - Reminder & Notification Flow

```mermaid
graph TB
    USER[👤 User]
    CRON[⏰ Cron Job/<br/>Queue Worker]
    
    subgraph CREATE_REMINDER["Create Reminder"]
        CR_INPUT[Input:<br/>schedule_id,<br/>minutes_before]
        CR_STORE[Store<br/>Reminder]
    end
    
    subgraph PROCESS_REMINDER["Process Reminder (Background)"]
        PR_FETCH[Fetch Pending<br/>Reminders]
        PR_CHECK[Check<br/>Time to Send]
        PR_GET_SCHEDULE[Get Schedule<br/>Details]
        PR_GET_USERS[Get Users<br/>to Notify]
    end
    
    subgraph SEND_NOTIFICATION["Send Notification"]
        SN_GET_TOKENS[Get Device<br/>Tokens]
        SN_SEND_FCM[Send to<br/>Firebase]
        SN_LOG[Log<br/>Notification]
        SN_UPDATE[Update Reminder<br/>Status]
    end
    
    DB_REMINDERS[(D7: reminders)]
    DB_CLASS_SCHED[(D5: class_schedules)]
    DB_PERSONAL[(D6: personal_schedules)]
    DB_TOKENS[(D2: device_tokens)]
    DB_NOTIF[(D8: notifications)]
    FCM[🔔 Firebase<br/>Cloud Messaging]

    USER --> CR_INPUT
    CR_INPUT --> CR_STORE
    CR_STORE --> DB_REMINDERS

    CRON --> PR_FETCH
    PR_FETCH <--> DB_REMINDERS
    PR_FETCH --> PR_CHECK
    PR_CHECK --> PR_GET_SCHEDULE
    PR_GET_SCHEDULE --> DB_CLASS_SCHED
    PR_GET_SCHEDULE --> DB_PERSONAL
    PR_GET_SCHEDULE --> PR_GET_USERS
    
    PR_GET_USERS --> SN_GET_TOKENS
    SN_GET_TOKENS <--> DB_TOKENS
    SN_GET_TOKENS --> SN_SEND_FCM
    SN_SEND_FCM --> FCM
    FCM -->|Push Notification| USER
    
    SN_SEND_FCM --> SN_LOG
    SN_LOG --> DB_NOTIF
    SN_LOG --> SN_UPDATE
    SN_UPDATE --> DB_REMINDERS
```

---

## 📊 Cardinality Summary

| Relationship | From | To | Type |
|-------------|------|-----|------|
| User owns Classrooms | USERS | CLASSROOMS | 1:N |
| User joins Classrooms | USERS | CLASSROOMS | N:M (via classroom_user) |
| Classroom has Schedules | CLASSROOMS | CLASS_SCHEDULES | 1:N |
| User creates Personal Schedules | USERS | PERSONAL_SCHEDULES | 1:N |
| User coordinates Schedules | USERS | CLASS_SCHEDULES | 1:N (coord_1, coord_2) |
| Schedule has Reminders | SCHEDULES | REMINDERS | 1:N (polymorphic) |
| User has Device Tokens | USERS | DEVICE_TOKENS | 1:N |
| User receives Notifications | USERS | NOTIFICATIONS | 1:N |

---

## 🎯 Key Features Visible in ERD/DFD

### ✅ Multi-tenancy via Classrooms
- Users can own multiple classrooms
- Users can join multiple classrooms as members
- Proper separation of data per classroom

### ✅ Flexible Scheduling
- Class schedules tied to classrooms
- Personal schedules independent
- Combined view for unified calendar

### ✅ Collaborative Features
- Dual coordinators per class schedule
- Classroom ownership transfer
- Member management

### ✅ Notification System
- Polymorphic reminders (works for both schedule types)
- Push notification via Firebase
- Notification history tracking

### ✅ Data Integrity
- Soft deletes for audit trail
- Proper cascade/restrict rules
- Unique constraints for business logic

---

**Cara Melihat Diagram Mermaid:**
1. Buka file ini di GitHub - diagram otomatis ter-render
2. Install extension "Markdown Preview Mermaid Support" di VS Code
3. Copy paste ke https://mermaid.live untuk edit/export
4. Export ke PNG/SVG untuk dokumentasi

**Generated**: December 17, 2025
