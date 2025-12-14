const API_URL = "api/index.php";

let currentUser = null;
let printers = [];

// ---------- Helpers ----------
function showAuth() {
  document.getElementById("auth-area").classList.remove("hidden");
  document.getElementById("dashboard").classList.add("hidden");
}

function showDashboard() {
  document.getElementById("auth-area").classList.add("hidden");
  document.getElementById("dashboard").classList.remove("hidden");
}

// Estimate cost (very simple)
function calculateCost() {
  const pages = Number(document.getElementById("pages").value) || 0;
  const copies = Number(document.getElementById("copies").value) || 1;
  const colorMode = document.getElementById("color-mode").value;

  let rate = colorMode === "color" ? 0.4 : 0.1; // ₹ per page (example)
  const cost = pages * copies * rate;
  document.getElementById("cost-display").innerText = cost.toFixed(2);
  return cost;
}

// ---------- Event Listeners ----------
document.getElementById("login-form").addEventListener("submit", async (e) => {
  e.preventDefault();
  const email = document.getElementById("login-email").value.trim();

  const formData = new FormData();
  formData.append("email", email);

  const res = await fetch(`${API_URL}?action=login`, {
    method: "POST",
    body: formData
  });
  const data = await res.json();

  const msgEl = document.getElementById("auth-message");
  if (data.success) {
    currentUser = data.user;
    msgEl.innerText = "";
    initDashboard();
  } else {
    msgEl.innerText = data.message || "Login failed";
  }
});

document.getElementById("register-form").addEventListener("submit", async (e) => {
  e.preventDefault();
  const name = document.getElementById("reg-name").value.trim();
  const email = document.getElementById("reg-email").value.trim();
  const dept = document.getElementById("reg-dept").value.trim();

  const formData = new FormData();
  formData.append("name", name);
  formData.append("email", email);
  formData.append("department", dept);

  const res = await fetch(`${API_URL}?action=register`, {
    method: "POST",
    body: formData
  });
  const data = await res.json();

  const msgEl = document.getElementById("auth-message");
  if (data.success) {
    currentUser = data.user;
    msgEl.innerText = "";
    initDashboard();
  } else {
    msgEl.innerText = data.message || "Registration failed";
  }
});

document.getElementById("logout-btn").addEventListener("click", () => {
  currentUser = null;
  showAuth();
});

document.getElementById("pages").addEventListener("input", calculateCost);
document.getElementById("copies").addEventListener("input", calculateCost);
document.getElementById("color-mode").addEventListener("change", calculateCost);

// Submit job
document.getElementById("job-form").addEventListener("submit", async (e) => {
  e.preventDefault();
  if (!currentUser) return;

  const fileName = document.getElementById("file-name").value.trim();
  const pages = document.getElementById("pages").value;
  const copies = document.getElementById("copies").value;
  const colorMode = document.getElementById("color-mode").value;
  const paperSize = document.getElementById("paper-size").value;
  const printerId = document.getElementById("printer-select").value;
  const cost = calculateCost();

  const formData = new FormData();
  formData.append("userId", currentUser.id);
  formData.append("fileName", fileName);
  formData.append("pages", pages);
  formData.append("copies", copies);
  formData.append("colorMode", colorMode);
  formData.append("paperSize", paperSize);
  formData.append("printerId", printerId);
  formData.append("cost", cost.toString());

  const res = await fetch(`${API_URL}?action=submitJob`, {
    method: "POST",
    body: formData
  });
  const data = await res.json();

  const msgEl = document.getElementById("job-message");
  msgEl.innerText = data.message || "";

  if (data.success) {
    // refresh jobs & balance
    await loadJobs();
    await refreshUserBalance();
    document.getElementById("job-form").reset();
    calculateCost();
  }
});

// ---------- Load data ----------
async function loadPrinters() {
  const res = await fetch(`${API_URL}?action=printers`);
  printers = await res.json();
  const select = document.getElementById("printer-select");
  select.innerHTML = "";
  printers.forEach(p => {
    const opt = document.createElement("option");
    opt.value = p.id;
    opt.textContent = `${p.name} (${p.status})`;
    select.appendChild(opt);
  });
}

async function loadJobs() {
  if (!currentUser) return;
  const res = await fetch(`${API_URL}?action=jobs&userId=${currentUser.id}`);
  const jobs = await res.json();

  const tbody = document.querySelector("#jobs-table tbody");
  tbody.innerHTML = "";
  jobs.forEach(j => {
    const tr = document.createElement("tr");
    tr.innerHTML = `
      <td>${j.file_name}</td>
      <td>${j.printer_name}</td>
      <td>${j.pages}</td>
      <td>${j.copies}</td>
      <td>${j.status}</td>
      <td>₹${Number(j.cost).toFixed(2)}</td>
      <td>${j.created_at}</td>
    `;
    tbody.appendChild(tr);
  });
}

async function refreshUserBalance() {
  // simple re-login to get updated balance
  const formData = new FormData();
  formData.append("email", currentUser.email);
  const res = await fetch(`${API_URL}?action=login`, {
    method: "POST",
    body: formData
  });
  const data = await res.json();
  if (data.success) {
    currentUser = data.user;
    document.getElementById("user-balance").innerText = currentUser.balance.toFixed(2);
  }
}

// Initialize dashboard after login/register
async function initDashboard() {
  document.getElementById("user-name").innerText = currentUser.name;
  document.getElementById("user-role").innerText = currentUser.role;
  document.getElementById("user-balance").innerText = Number(currentUser.balance).toFixed(2);

  await loadPrinters();
  await loadJobs();
  calculateCost();
  showDashboard();
}

