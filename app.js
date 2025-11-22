// ========================= CONFIG =========================
const API_BASE = "http://localhost/studyplan_app/api/";

// ========================= AUTH FUNCTIONS =========================

// ---- LOGIN ----
async function login(email, password) {
  const res = await fetch("http://localhost/studyplan_app/api/login.php", {
    method: "POST",
    credentials: "include",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ email, password }),
  });

  const data = await res.json();

  if (data.success) {
    // Redirect to homepage
    window.location.href = "index.html";
  } else {
    alert(data.message || "Login failed!");
  }
}

// ---- CHECK AUTH STATUS ----
async function checkAuth() {
  const res = await fetch(API_BASE + "me.php", {
    method: "GET",
    credentials: "include",
  });

  const data = await res.json();

  // If NOT logged in → send user to login page
  if (!data.success) {
    window.location.href = "login.html";
    return;
  }

  return data;
}

// ---- LOGOUT ----
async function logout() {
  const res = await fetch(API_BASE + "logout.php", {
    method: "GET",
    credentials: "include",
  });

  const data = await res.json();

  if (data.success) {
    // Clear browser cache + redirect
    sessionStorage.clear();
    localStorage.clear();

    window.location.href = "login.html";
  } else {
    alert("Logout failed!");
  }
}

// ========================= Login=========================
async function login(email, password) {
  const res = await fetch(API_BASE + "login.php", {
    method: "POST",
    credentials: "include", // ⭐ REQUIRED
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ email, password }),
  });

  const data = await res.json();
  return data;
}

// ========================= Logout =========================
async function logout() {
  try {
    const res = await fetch(API_BASE + "logout.php", {
      method: "GET",
      credentials: "include",
    });

    const data = await res.json();

    if (data.success) {
      // Redirect to login page
      window.location.href = "login.html";
    } else {
      alert("Logout failed!");
    }
  } catch (err) {
    console.error("Logout error:", err);
    alert("Network error while logging out.");
  }
}

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
    const res = await fetch(API_BASE + "subjects.php", {
      credentials: "include"
    });
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
            <button class="btn-edit" data-id="${s.id}" data-title="${s.title.replace(/\"/g, '&quot;')}" data-color="${s.color}" data-hours="${s.planned_hours}" data-percent="${s.completed_percent}">Edit</button>
            <button class="btn-delete" data-id="${s.id}">Delete</button>
            <button class="btn-syllabus" data-id="${s.id}">Syllabus</button>
          </div>

          <div class="syllabus-container" id="syllabus-${s.id}" style="margin-top:10px;display:none;"></div>
        </div>
      `
          )
          .join("")
      : "<p>No subjects yet. Click Add Subject to create one.</p>";

    // Add event listeners to Edit buttons
    document.querySelectorAll(".btn-edit").forEach(btn => {
      btn.addEventListener("click", () => {
        const modal = document.getElementById("modal");
        const form = document.getElementById("modalForm");
        document.getElementById("modalTitle").textContent = "Edit Subject";
        form.querySelector('input[name="id"]').value = btn.dataset.id;
        form.querySelector('input[name="title"]').value = btn.dataset.title;
        form.querySelector('input[name="color"]').value = btn.dataset.color;
        form.querySelector('input[name="planned_hours"]').value = btn.dataset.hours;
        form.querySelector('input[name="completed_percent"]').value = btn.dataset.percent;
        modal.classList.remove("hidden");
      });
    });

    // Add event listeners to Delete buttons
    document.querySelectorAll(".btn-delete").forEach(btn => {
      btn.addEventListener("click", async () => {
        const id = btn.dataset.id;
        if (confirm("Are you sure you want to delete this subject?")) {
          await deleteSubject(id);
        }
      });
    });

    // Add event listeners to Syllabus buttons
    document.querySelectorAll('.btn-syllabus').forEach(btn => {
      btn.addEventListener('click', async () => {
        const id = btn.dataset.id;
        const container = document.getElementById(`syllabus-${id}`);
        if (!container) return;
        if (container.style.display === 'none' || container.style.display === '') {
          container.style.display = 'block';
          await loadSyllabus(id, container);
        } else {
          container.style.display = 'none';
        }
      });
    });

  } catch (err) {
    console.error("Error loading subjects:", err);
  }
}

async function addOrUpdateSubject(subject) {
  try {
    const res = await fetch(API_BASE + "subjects.php", {
      method: "POST",
      credentials: "include",
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
  try {
    const res = await fetch(`${API_BASE}subjects.php?id=${id}`, {
      method: "DELETE",
      credentials: "include"
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
async function loadTasks() {
  try {
    const res = await fetch(API_BASE + "calendar.php", {
      credentials: "include"
    });
    const data = await res.json();

    const list = document.getElementById("tasksList");
    if (!list) return;

    if (data.length === 0) {
      list.innerHTML = '<div style="text-align:center;padding:40px;color:#9ca3af;"><p>No tasks yet. <strong>Click "Add Task" to create one!</strong></p></div>';
      return;
    }

    list.innerHTML = data
      .map(
        (t) => `
        <div class="card task ${t.done ? "done" : ""}" style="display:flex;align-items:center;justify-content:space-between;padding:15px;margin-bottom:10px;border-left:4px solid ${t.priority === 'high' ? '#ef4444' : t.priority === 'medium' ? '#f59e0b' : '#10b981'};">
          <div style="display:flex;align-items:center;gap:15px;flex:1;">
            <input type="checkbox" onchange="toggleTaskDone(${t.id}, this.checked)" ${t.done ? "checked" : ""} style="width:20px;height:20px;cursor:pointer;">
            <div>
              <div style="font-weight:bold;font-size:16px;${t.done ? 'text-decoration:line-through;color:#9ca3af;' : ''}">${t.title}</div>
              <div style="font-size:12px;color:#6b7280;margin-top:5px;">
                📚 ${t.subject_name || "No subject"} | 📅 ${t.due_date || "No date"} | Priority: <span style="color:${t.priority === 'high' ? '#ef4444' : t.priority === 'medium' ? '#f59e0b' : '#10b981'};font-weight:bold;">${t.priority.toUpperCase()}</span>
              </div>
            </div>
          </div>
          <button class="btn-delete-task" data-id="${t.id}" style="background-color:#ef4444;color:white;padding:8px 12px;border:none;border-radius:4px;cursor:pointer;font-size:12px;">Delete</button>
        </div>
      `
      )
      .join("");

    // Add event listeners for delete buttons
    document.querySelectorAll(".btn-delete-task").forEach(btn => {
      btn.addEventListener("click", async () => {
        const id = btn.dataset.id;
        if (confirm("Are you sure you want to delete this task?")) {
          await deleteTask(id);
        }
      });
    });
  } catch (err) {
    console.error("Error loading tasks:", err);
  }
}

// ========================= STATS & DASHBOARD =========================
async function loadDashboardStats() {
  try {
    const res = await fetch(API_BASE + 'stats.php', { credentials: 'include' });
    if (!res.ok) return;
    const s = await res.json();
    document.getElementById('studyHours').textContent = (s.study_hours || 0) + 'h';
    document.getElementById('activeSubjects').textContent = (s.active_subjects || 0);
    document.getElementById('goalsCompleted').textContent = (s.done_tasks || 0) + '/' + (s.total_tasks || 0);
    document.getElementById('streak').textContent = (s.streak || 0) + ' days';
  } catch (err) {
    console.error('Error loading dashboard stats:', err);
  }
}

// ========================= SYLLABUS UI & API HELPERS =========================
async function loadSyllabus(subjectId, container) {
  try {
    const res = await fetch(API_BASE + `syllabus.php?subject_id=${subjectId}`, { credentials: 'include' });
    if (!res.ok) { container.innerHTML = '<div class="muted">Could not load syllabus</div>'; return; }
    const items = await res.json();
    container.innerHTML = `
      <ul class="syllabus-list">
        ${items.map(it => `<li data-id="${it.id}" class="syllabus-item ${it.done ? 'done' : ''}">
            <input type="checkbox" data-id="${it.id}" ${it.done ? 'checked' : ''} class="syllabus-toggle"> ${it.title}
            <button class="syllabus-delete" data-id="${it.id}">Delete</button>
          </li>`).join('')}
      </ul>
      <div style="margin-top:8px;display:flex;gap:8px;">
        <input placeholder="Add syllabus item" class="syllabus-input" style="flex:1;padding:6px;border:1px solid #ddd;border-radius:4px;">
        <button class="syllabus-add">Add</button>
      </div>`;

    // Attach handlers
    container.querySelectorAll('.syllabus-toggle').forEach(ch => {
      ch.addEventListener('change', async () => {
        const id = ch.dataset.id;
        const done = ch.checked ? 1 : 0;
        await fetch(API_BASE + 'syllabus.php', { method: 'PUT', credentials: 'include', headers: {'Content-Type':'application/json'}, body: JSON.stringify({ id: id, done: done }) });
        await loadSyllabus(subjectId, container);
      });
    });

    container.querySelectorAll('.syllabus-delete').forEach(btn => {
      btn.addEventListener('click', async () => {
        const id = btn.dataset.id;
        if (!confirm('Delete syllabus item?')) return;
        await fetch(API_BASE + `syllabus.php?id=${id}`, { method: 'DELETE', credentials: 'include' });
        await loadSyllabus(subjectId, container);
      });
    });

    const input = container.querySelector('.syllabus-input');
    const addBtn = container.querySelector('.syllabus-add');
    addBtn.addEventListener('click', async () => {
      const title = input.value.trim();
      if (!title) return;
      await fetch(API_BASE + 'syllabus.php', { method: 'POST', credentials: 'include', headers: {'Content-Type':'application/json'}, body: JSON.stringify({ subject_id: subjectId, title: title }) });
      input.value = '';
      await loadSyllabus(subjectId, container);
    });

  } catch (err) {
    console.error('Error loading syllabus:', err);
    container.innerHTML = '<div class="muted">Error loading syllabus</div>';
  }
}

async function deleteTask(id) {
  try {
    console.log("Deleting task id=", id);
    const res = await fetch(`${API_BASE}calendar.php?id=${id}`, {
      method: "DELETE",
      credentials: "include"
    });

    if (!res.ok) {
      const txt = await res.text().catch(() => "");
      console.error("Delete request failed:", res.status, txt);
      alert("Could not delete task. Server returned " + res.status);
      return;
    }

    const json = await res.json().catch(() => null);
    if (json && json.success) {
      console.log("Task deleted successfully", id);
      await loadTasks();
      await loadDashboardTasks();
    } else {
      console.error("Delete returned error:", json);
      alert("Failed to delete task: " + (json && (json.error || json.message) ? (json.error || json.message) : "unknown error"));
    }
  } catch (err) {
    console.error("Error deleting task:", err);
    alert("Network error while deleting task. See console.");
  }
}

async function toggleTaskDone(id, done) {
  try {
    await fetch(API_BASE + "calendar.php", {
      method: "PUT",
      credentials: "include",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ id, done: done ? 1 : 0 }),
    });
    await loadTasks();
    await loadDashboardTasks();
  } catch (err) {
    console.error("Error toggling task:", err);
  }
}

// ========================= ADD TASK =========================
async function addTask(task) {
  try {
    if (!task.title || !task.title.trim()) {
      alert("Task title is required");
      return;
    }

    const taskData = {
      title: task.title.trim(),
      priority: task.priority || "medium",
      due_date: task.due_date || null,
      subject_id: null
    };
    
    console.log("Sending task data:", taskData);
    
    const res = await fetch(API_BASE + "calendar.php", {
      method: "POST",
      credentials: "include",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(taskData)
    });

    if (!res.ok) {
      throw new Error(`HTTP ${res.status}: ${res.statusText}`);
    }

    const data = await res.json();
    console.log("Response from server:", data);

    if (data.success) {
      alert("✅ Task added successfully!");
      await loadTasks();
      await loadDashboardTasks();
    } else {
      alert("❌ " + (data.error || "Failed to add task"));
    }
  } catch (err) {
    console.error("Error in addTask:", err);
    alert("❌ Error: " + err.message);
  }
}

// ========================= DASHBOARD UPCOMING TASKS =========================
async function loadDashboardTasks() {
  const list = document.getElementById("dashboardTasks");
  if (!list) return;

  try {
    const res = await fetch(API_BASE + "calendar.php", {
      credentials: "include"
    });
    const data = await res.json();

    list.innerHTML = data
      .filter((t) => !t.done)
      .map(
        (t) => `
        <div class="card task">
          <div style="display:flex;align-items:center;gap:10px;">
            <input type="checkbox" onchange="toggleTaskDone(${t.id}, this.checked)">
            <span><strong>${t.title}</strong></span>
          </div>
          <div style="font-size:12px;color:#9ca3af;margin-top:5px;">
            📚 ${t.subject_name || "No subject"} | 📅 ${t.due_date || "No date"} | 🎯 ${t.priority}
          </div>
        </div>
      `
      )
      .join("");

    if (data.filter((t) => !t.done).length === 0) {
      list.innerHTML = "<p style='text-align:center;color:#9ca3af;'>No upcoming tasks. You're all caught up! 🎉</p>";
    }
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

  if (path === "login.html" || path === "" || path === "signup.html") return;

  const auth = await checkAuth();
  if (!auth.success || !auth.loggedIn) {
    window.location.href = "login.html";
    return;
  }

  if (document.getElementById("subjectsGrid")) loadSubjects();
  if (document.getElementById("tasksList")) loadTasks();
  if (document.getElementById("dashboardTasks")) loadDashboardTasks();
  if (document.getElementById("studyHours") || document.getElementById("activeSubjects")) loadDashboardStats();
  if (document.getElementById("calendarContainer")) loadCalendar();
});
