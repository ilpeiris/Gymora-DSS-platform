# CareFit - Medical-Integrated Gym Management System 🏥🏋️‍♂️

![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?style=flat-square&logo=php)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=flat-square&logo=mysql)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=flat-square&logo=bootstrap)
![Chart.js](https://img.shields.io/badge/Chart.js-Data_Viz-FF6384?style=flat-square)
![License](https://img.shields.io/badge/License-MIT-green?style=flat-square)

## 📌 Executive Summary
**CareFit** is a commercial-grade Business Information System (BIS) designed to bridge the gap between clinical healthcare and commercial fitness. 

Standard gym management software (like MindBody or ZenPlanner) focuses purely on operational billing and scheduling. CareFit introduces a deterministic, **Rule-Based Decision Support System (DSS)** that cross-references a user's structured medical diagnoses against exercise contraindications, autonomously blocking unsafe activities to prevent injury. 

Built with strict adherence to **UK GDPR (Data Protection Act 2018)**, the platform features robust Role-Based Access Control (RBAC) and automated immutable audit trails for handling Special Category medical data.

---

## 🚀 Core Technical Highlights (For Technical Reviewers)

### 1. Rule-Based Decision Support System (DSS)
* **Algorithmic Safety:** A custom PHP/SQL engine that intercepts class bookings and workout prescriptions. It evaluates the user's active `medical_conditions` against a `dss_rules` matrix.
* **Deterministic Logic:** If a clinical conflict is detected (e.g., a user with a Lumbar Disc Herniation attempts to book a High-Impact lifting class), the transaction is aborted server-side, and a sanitized UI warning is returned.

### 2. Enterprise-Level Data Governance & Security
* **Automated GDPR Audit Logger:** Any interaction with Special Category Data (Medical Reports, Diagnoses) triggers a background `logAudit()` function. This immutably records the Actor ID, Target ID, Timestamp, Action, and IP Address.
* **Strict RBAC Gateway:** A centralized session-management protocol prevents horizontal and vertical privilege escalation across the 4 distinct user portals (Admin, Doctor, Trainer, User).
* **PDO Security:** 100% adherence to parameterized PHP Data Objects (PDO) to neutralize SQL injection vectors.

### 3. Business Intelligence (BI) & Real-Time Comms
* **Analytics Dashboard:** Integration with Chart.js to translate raw relational database metrics into interactive visual insights (Revenue, Retention, Injury Distribution, Class Popularity).
* **AJAX Polling Chat:** A lightweight, vanilla JavaScript asynchronous communication system allowing real-time, secure messaging between patients, doctors, and trainers.

---

## 👥 Multi-Tier Architecture

The system routes users dynamically into isolated portals based on their cryptographic session token:
1. **The User Portal:** Consumer dashboard for class booking, progress tracking (BMI/Weight graphs), and medical clearances.
2. **The Doctor Portal:** Clinical gateway for submitting encrypted, structured medical assessments and diagnosing contraindications.
3. **The Trainer Portal:** Operational hub for building DSS-filtered workout plans and managing class rosters.
4. **The Admin Portal:** Governance hub featuring BI analytics, system configuration, and GDPR audit log monitoring.

---

## 🛠️ Local Installation & Setup

To run this application locally for review:

1. **Clone the repository:**
   ```bash
   git clone [https://github.com/YourUsername/CareFit.git](https://github.com/YourUsername/CareFit.git)
