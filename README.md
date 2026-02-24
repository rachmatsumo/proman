# ProMan - Project Management System

**ProMan** is a comprehensive, web-based Project Management System explicitly tailored for tracking and managing project hierarchies, schedules, and deliverables for your project.

## 🌟 Key Features

- **Hierarchical Project Tracking**
  Organize your work seamlessly with a robust 4-level deep structure:
  `Program` ➔ `Sub Program` ➔ `Milestone` ➔ `Activity`

- **Visual Timelines (Gantt Charts)**
  Built-in interactive Gantt charts (powered by *Frappe Gantt*) that automatically map out dates, duration, and track progress across the entire project structure. Includes smart auto-scrolling to the most relevant dates.

- **Activity Calendar**
  A dynamic and responsive calendar view (powered by *FullCalendar*) to track daily activities, allowing managers to see schedules at a glance and jump straight into details.

- **Automated Progress Calculation**
  Progress percentages are mathematically aggregated from the bottom-up (`Activities` 📈 `Milestones` 📈 `Sub Programs` 📈 `Programs`) based on custom weightings (*Bobot*).

- **Document Management**
  Attach project files, design assets, and review documents at any tier of the project hierarchy for centralized file management.

- **Interactive UI/UX**
  Built with *Bootstrap 5*, featuring AJAX-loaded tabs, expandable/collapsible data accordions, and real-time live search for navigating complex project trees rapidly without page reloads.

## 🛠️ Technology Stack

- **Backend:** Laravel 12, PHP 8.3+
- **Frontend:** Bootstrap 5, CSS3, Vanilla JavaScript
- **Database:** MySQL / SQLite
- **Libraries:** Frappe Gantt (Timeline), FullCalendar, FontAwesome 6

## 🚀 Installation & Setup

1. **Clone the repository:**
   ```bash
   git clone https://github.com/rachmatsumo/proman.git
   cd proman
   ```

2. **Install PHP Dependencies:**
   ```bash
   composer install
   ```

3. **Configure Environment:**
   Copy the example environment file and configure your database settings.
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Run Migrations & Seeders (Optional but Recommended):**
   ```bash
   php artisan migrate --seed
   ```

5. **Start the Development Server:**
   ```bash
   php artisan serve
   ```
   *Visit `http://localhost:8000` in your browser.*

## 📄 License
This application is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
