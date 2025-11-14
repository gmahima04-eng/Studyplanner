// ========================= CONFIG =========================
const API_BASE = "http://localhost/studyplan_app/api/";

// ========================= AUTH FUNCTIONS =========================
async function checkAuth() {
  try {
    const res = await fetch(API_BASE + "me.php", {
      method: "GET",
      credentials: "same-origin",
    });
    return await res.json();
  } catch (err) {
    console.error("Auth check failed:", err);
    return { loggedIn: false };
  }
}

async function logoutUser() {
  try {
    await fetch(API_BASE + "logout.php", {
      method: "POST",
      credentials: "same-origin",
    });
  } catch {}
  localStorage.removeItem("sp_user");
  window.location.href = "login.html";
}

document.getElementById("logoutBtn")?.addEventListener("click", logoutUser);

// ========================= SUBJECTS =========================

// ⭐ NEW FUNCTION — Trigger Add Subject Prompt
function createSubject() {
  const title = prompt("Enter subject name:");
  if (!title) return;

  addOrUpdateSubject({
    title: title,
    color: "#60a5fa",
    planned_hours: 0,
    completed_percent: 0,
  });
}

async function loadSubjects() {
  try {
    const res = await fetch(API_BASE + "subjects.php");
    if (!res.ok) {
      console.error("Failed to load subjects:", res.status, await res.text());
      return;
    }
    const data = await res.json();

    const grid = document.getElementById("subjectsGrid");
    if (!grid) return;

    grid.innerHTML = data.length
      ? data
          .map(
            (s) => `
        <div class="card subject-card" style="border-left:5px solid ${s.color}">
          <h3>${s.title}</h3>
          <p>Planned: ${s.planned_hours}h</p>
          <div class="progress" data-percent="${s.completed_percent}">
            <span style="width:${s.completed_percent}%"></span>
          </div>
          <p>Completed: ${s.completed_percent}%</p>

          <div style="display:flex;gap:8px;">
            <button onclick="editSubject(${s.id}, '${s.title}', '${s.color}', ${s.planned_hours}, ${s.completed_percent})">Edit</button>
            <button onclick="deleteSubject(${s.id})">Delete</button>
          </div>
        </div>
      `
          )
          .join("")
      : "<p>No subjects yet. Click Add Subject to create one.</p>";
  } catch (err) {
    console.error("Error loading subjects:", err);
  }
}

async function addOrUpdateSubject(subject) {
  try {
    const res = await fetch(API_BASE + "subjects.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(subject),
    });

    const json = await res.json().catch(() => null);

    if (!res.ok) {
      const msg = (json && json.error) || `Server returned ${res.status}`;
      console.error("Add/Update subject failed:", msg);
      alert("Could not save subject: " + msg);
      return false;
    }

    // success - reload list
    await loadSubjects();
    return true;
  } catch (err) {
    console.error("Network error while saving subject:", err);
    alert("Network error while saving subject. Check console.");
    return false;
  }
}

async function deleteSubject(id) {
  if (!confirm("Are you sure you want to delete this subject?")) return;
  try {
    const res = await fetch(`${API_BASE}subjects.php?id=${id}`, {
      method: "DELETE",
    });
    if (!res.ok) {
      console.error("Delete failed:", res.status, await res.text());
      alert("Could not delete subject. See console.");
      return;
    }
    await loadSubjects();
  } catch (err) {
    console.error("Network error deleting subject:", err);
    alert("Network error deleting subject. Check console.");
  }
}

// ========================= TASKS PAGE =========================
// FIXED: corrected wrong API endpoint
async function loadTasks() {
  try {
    const res = await fetch(API_BASE + "calendar.php");
    const data = await res.json();

    const list = document.getElementById("tasksList");
    if (!list) return;

    list.innerHTML = data
      .map(
        (t) => `
        <div class="card task ${t.done ? "done" : ""}">
          <div style="display:flex;align-items:center;gap:10px;">
            <input type="checkbox" onchange="toggleTaskDone(${
              t.id
            }, this.checked)" ${t.done ? "checked" : ""}>
            <strong>${t.title}</strong>
          </div>

          <div style="font-size:13px;color:#6b7280;">
            ${t.subject_name || "N/A"} • Due: ${
          t.due_date || "—"
        } • Priority: ${t.priority}
          </div>

          <button onclick="deleteTask(${t.id})">Delete</button>
        </div>
      `
      )
      .join("");
  } catch (err) {
    console.error("Error loading tasks:", err);
  }
}

async function deleteTask(id) {
  if (confirm("Are you sure you want to delete this task?")) {
    await fetch(`${API_BASE}calendar.php?id=${id}`, { method: "DELETE" });
    loadTasks();
    loadDashboardTasks();
  }
}

async function toggleTaskDone(id, done) {
  await fetch(API_BASE + "calendar.php", {
    method: "PUT",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ id, done: done ? 1 : 0 }),
  });
  loadTasks();
  loadDashboardTasks();
}

// ========================= DASHBOARD UPCOMING TASKS =========================
async function loadDashboardTasks() {
  const list = document.getElementById("dashboardTasks");
  if (!list) return;

  try {
    const res = await fetch(API_BASE + "calendar.php");
    const data = await res.json();

    list.innerHTML = data
      .filter((t) => !t.done)
      .map(
        (t) => `
        <div class="card task">
          <div style="display:flex;align-items:center;gap:8px;">
            <input type="checkbox" onchange="toggleTaskDone(${t.id}, this.checked)">
            <span>${t.title} (${t.priority})</span>
          </div>
        </div>
      `
      )
      .join("");
  } catch (err) {
    console.error("Error loading dashboard tasks:", err);
  }
}

// ========================= CALENDAR PAGE =========================
async function loadCalendar() {
  try {
    const res = await fetch(API_BASE + "calendar.php");
    const data = await res.json();

    const div = document.getElementById("calendarContainer");
    if (!div) return;

    if (!data.length) {
      div.innerHTML = "<p>No upcoming tasks found.</p>";
      return;
    }

    div.innerHTML = `
      <table border="1" cellspacing="0" cellpadding="8">
        <thead>
          <tr><th>Due Date</th><th>Task</th><th>Priority</th><th>Subject</th></tr>
        </thead>
        <tbody>
          ${data
            .map(
              (t) => `
              <tr>
                <td>${new Date(t.due_date).toLocaleDateString()}</td>
                <td>${t.title}</td>
                <td>${t.priority}</td>
                <td>${t.subject_name || "—"}</td>
              </tr>`
            )
            .join("")}
        </tbody>
      </table>`;
  } catch (err) {
    console.error("Error loading calendar:", err);
  }
}

// ========================= ADD TASK (Calendar Form) =========================
const calendarForm = document.getElementById("calendarTaskForm");
if (calendarForm) {
  calendarForm.addEventListener("submit", async (e) => {
    e.preventDefault();
    const title = document.getElementById("taskTitle").value.trim();
    const due_date = document.getElementById("taskDate").value;
    const priority = document.getElementById("taskPriority").value;

    if (!title || !due_date) {
      alert("Please fill in all fields.");
      return;
    }

    await fetch(API_BASE + "calendar.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ title, due_date, priority }),
    });

    alert("Upcoming task added successfully!");
    e.target.reset();
    loadCalendar();
    loadTasks();
    loadDashboardTasks();
  });
}

// ========================= INITIAL PAGE LOAD =========================
document.addEventListener("DOMContentLoaded", async () => {
  const path = location.pathname.split("/").pop();

  if (path === "login.html" || path === "") return;

  const auth = await checkAuth();
  if (!auth.loggedIn) {
    window.location.href = "login.html";
    return;
  }

  if (document.getElementById("subjectsGrid")) loadSubjects();
  if (document.getElementById("tasksList")) loadTasks();
  if (document.getElementById("dashboardTasks")) loadDashboardTasks();
  if (document.getElementById("calendarContainer")) loadCalendar();
});
