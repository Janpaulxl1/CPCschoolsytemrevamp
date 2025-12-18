    <?php
  require 'db.php';

  /* ============================
    SAFETY CHECK
  ============================ */
  $programs = [];
  $sections = [];

  if ($conn) {

      /* ============================
        FETCH PROGRAMS
      ============================ */
      $result = $conn->query("SELECT id, name FROM programs ORDER BY name ASC");
      if ($result) {
          while ($row = $result->fetch_assoc()) {
              $programs[(int)$row['id']] = $row['name'];
          }
      }

      /* ============================
        FETCH SECTIONS
      ============================ */
      $result = $conn->query("
          SELECT s.id, s.name, s.semester, s.is_archived,
                p.name AS program, p.id AS program_id
          FROM sections s
          JOIN programs p ON s.program_id = p.id
          ORDER BY p.name ASC, s.name ASC
      ");
      if ($result) {
          while ($row = $result->fetch_assoc()) {
              $pid = (int)$row['program_id'];
              if (!isset($sections[$pid])) $sections[$pid] = [];
              $sections[$pid][] = $row;
          }
      }
  }
  ?>
  <!DOCTYPE html>
  <html lang="en">
  <head>
  <meta charset="UTF-8">
  <title>Student File Dashboard</title>

  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <style>
  body{
    background:rgb(243, 229, 229);
    min-height:100vh;
    font-family:Inter,sans-serif;
  }
  header{
    background: #b91c1c;
    position: relative;
  }
  #shell{
    display:grid;
    grid-template-columns:260px 1fr;
    gap:24px;
    padding:24px;
  }
   #sidebar{
    background:#fff;
    border-radius:18px;
    border:2px solid #b91c1c;
    padding:20px;
  } 
  .side-btn{  
    width:100%;
    padding:14px;
    border-radius:14px;     
    font-weight:700;
    margin-bottom:12px;
    transition:.2s;
    border: 1px solid #000;
  }
  .side-btn.program{background:#fff;color:#000;}
  .side-btn.program:hover{background:#b91c1c;color:#fff;font-weight:bold;}
  .side-btn.gray{background:#fff;color:#000;}
  .side-btn.gray:hover{background:#b91c1c;color:#fff;font-weight:bold;}

  #mainCard{
    background:#fff;
    border-radius:20px;
    border:2px solid #b91c1c;
    padding:28px;
    min-height:80vh;
  }
  #searchInput{
    background: #fff;
    color: #b91c1c;
    padding:12px 20px;
    border-radius:999px;
    width:340px;
    outline: 2px solid #b91c1c;
  }
  #searchInput::placeholder{color: #b91c1c}

.folder-item{text-align:center;cursor:pointer; position: relative; padding: 10px;}
.folder-icon{font-size:70px}
.folder-item.archived{opacity:.5}
.folder-name { cursor: pointer; }
.folder-title { display: flex; align-items: center; justify-content: center; gap: 5px; }

.actions { display: flex; gap: 4px; justify-content: center; margin-top: 8px; opacity: 0; transition: opacity 0.2s; }
.folder-item:hover .actions { opacity: 1; }
.folder-item button { padding: 4px 8px; border-radius: 4px; font-size: 12px; border: none; cursor: pointer; }
.archive-btn { background: #f3f4f6; color: #6b7280; }
.archive-btn:hover { background: #000000; color: #ffffff; font-weight: bold; }
.unarchive-btn { background: #fef3c7; color: #d97706; }
.unarchive-btn:hover { background: #000000; color: #ffffff; font-weight: bold; }
.delete-btn { background: #fee2e2; color: #dc2626; }
.delete-btn:hover { background: #b91c1c; color: #ffffff; font-weight: bold; }
.rename-input { border: 1px solid #ccc; padding: 2px 4px; border-radius: 4px; font-size: 14px; }
.rename-btn { background: #f9fafb; color: #374151; padding: 4px 8px; border-radius: 4px; font-size: 12px; border: none; cursor: pointer; font-weight: bold; }
.rename-btn:hover { background: #d1d5db; font-weight: bold; }

  table{border-collapse:collapse;width:100%}
  thead th{
    background:#b91c1c;
    color:#fff;
    padding:14px;
  }
  tbody td{
    border:1px solid #ddd;
    padding:14px;
  }

  </style>
  </head>

  <body>

  <header class="px-6 py-4">
    <h1 class="text-2xl font-bold text-white text-center">Student File Dashboard</h1>
  </header>

  <div id="shell">

  <!-- SIDEBAR -->
  <aside id="sidebar" class="border-t border-r border-b border-gray-300 p-4 w-64">
  <button onclick="window.location.href='nurse.php'" class="side-btn gray w-full mb-2">
    <i class="fas fa-arrow-left mr-2"></i> Back to Main Dashboard
  </button>

  <?php if ($programs): ?>
    <?php foreach ($programs as $id => $program): ?>
      <button class="side-btn program w-full mb-2"
        onclick='showProgram(<?= json_encode($program) ?>, <?= (int)$id ?>)'>
        <?= htmlspecialchars($program) ?>
      </button>
    <?php endforeach; ?>
  <?php else: ?>
    <div class="text-sm text-gray-500 text-center">
      No programs found
    </div>
  <?php endif; ?>

  <button onclick="showArchive()" class="side-btn gray w-full mt-2">
    <i class="fas fa-box-archive mr-2"></i> Archive
  </button>
</aside>

  <!-- MAIN -->
  <main id="mainCard">

  <div class="flex justify-center mb-8">
    <input id="searchInput" onkeyup="handleSearch()" placeholder="Search folder">
  </div>


  <!-- FOLDER VIEW -->
  <div id="folderView">
    <div class="flex justify-between items-center mb-6">
      <h2 id="programTitle" class="text-xl font-bold"></h2>
      <button onclick="openAddFolderModal()" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md flex items-center gap-2">
        <i class="fas fa-folder-plus"></i> Add Folder
      </button>
    </div>
    <div id="folders" class="grid grid-cols-2 md:grid-cols-4 gap-12"></div>
  </div>

<!-- STUDENT VIEW -->
<div id="studentView" class="hidden">
  <button onclick="location.reload()" class="mb-4 px-4 py-2 bg-red-700 text-white rounded hover:bg-red-600">Back to Folders</button>
  <h2 id="classTitle" class="text-xl font-bold mb-6"></h2>
    <div class="overflow-x-auto">
      <table>
        <thead>
          <tr>
            <th>No.</th>
            <th>Student ID</th>
            <th>Name</th>
            <th>Phone</th>
            <th>Email</th>
            <th>Requirements</th>
            <th>Emergency</th>
            <th>Appointment</th>
          </tr>
        </thead>
        <tbody id="studentTable"></tbody>
      </table>
    </div>
  </div>

  </main>
  </div>

  <!-- ADD FOLDER MODAL -->
  <div id="addFolderModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
    <div class="bg-white p-6 rounded shadow-lg w-96">
      <h3 class="text-lg font-bold mb-4">Add Folder</h3>
      <input id="newFolderName" class="w-full border p-2 mb-3" placeholder="Folder Name">
      <select id="newSemester" class="w-full border p-2 mb-3">
        <option>1st Semester</option>
        <option>2nd Semester</option>
        <option>Graduates</option>
      </select>
      <label class="flex items-center gap-2 mb-4">
        <input type="checkbox" id="newIsArchived"> Archive
      </label>
      <div class="flex justify-end gap-2">
        <button onclick="closeAddFolderModal()" class="px-4 py-1 bg-gray-400 text-white rounded">Cancel</button>
        <button onclick="addFolder()" class="px-4 py-1 bg-red-700 text-white rounded">Add</button>
      </div>
    </div>
  </div>

  <script>
  let currentProgramId = null;
  let currentSectionId = null;

  const sections = <?= json_encode($sections) ?>;
  const programs = <?= json_encode($programs) ?>;

  function openAddFolderModal(){
    document.getElementById('addFolderModal').classList.remove('hidden');
  }
  function closeAddFolderModal(){
    document.getElementById('addFolderModal').classList.add('hidden');
  }

  function showProgram(name,id){
    currentProgramId = String(id);
    document.getElementById('programTitle').innerText = name;
    document.getElementById('folderView').classList.remove('hidden');
    document.getElementById('studentView').classList.add('hidden');
    filterFolders();
  }

  function showArchive(){
    currentProgramId = "archive";
    document.getElementById('programTitle').innerText = "Archive";
    filterFolders();
  }

  function handleSearch(){ filterFolders(); }

  function filterFolders(){
    const container = document.getElementById('folders');
    const search = document.getElementById('searchInput').value.toLowerCase();
    container.innerHTML = '';

    let list = [];
    if(currentProgramId === "archive"){
      Object.values(sections).flat().forEach(f=>{
        if(+f.is_archived) list.push(f);
      });
    } else {
      list = (sections[currentProgramId] || []).filter(f => +f.is_archived === 0);
    }

  list.forEach(f=>{
    if(!f.name.toLowerCase().includes(search)) return;
    const d = document.createElement('div');
    d.className = "folder-item " + (+f.is_archived ? 'archived' : '');
    d.innerHTML = `<i class="fas fa-folder folder-icon"></i>
                   <div class="folder-title">
                     <span class="folder-name" ondblclick="editFolderName(event, ${JSON.stringify(f)})">${f.name}</span> - <span class="folder-semester">${f.semester}</span>
                   </div>
                   <div class="actions">
                     <button class="${+f.is_archived ? 'unarchive-btn' : 'archive-btn'}">${+f.is_archived ? 'Unarchive' : 'Archive'}</button>
                     <button class="rename-btn">Rename</button>
                     <button class="delete-btn">Delete</button>
                   </div>`;
    d.onclick = (e) => {
      if (e.target.tagName === 'BUTTON' || e.target.classList.contains('rename-input')) return;
      openFolder(f.id, `${f.name} - ${f.semester}`);
    };

    // Add event listeners for buttons
    const archiveBtn = d.querySelector('.archive-btn, .unarchive-btn');
    const renameBtn = d.querySelector('.rename-btn');
    const deleteBtn = d.querySelector('.delete-btn');
    if (archiveBtn) {
      archiveBtn.onclick = (e) => {
        e.stopPropagation();
        const newState = +f.is_archived ? 0 : 1;
        if (confirm(`Are you sure you want to ${newState ? 'archive' : 'unarchive'} this folder?`)) {
          fetch('archive_folder.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({section_id: f.id, is_archived: newState})
          })
          .then(r => r.json())
          .then(d => {
            if (d.success) {
              location.reload();
            } else {
              alert(d.message);
            }
          })
          .catch(e => alert('Network error'));
        }
      };
    }
    if (renameBtn) {
      renameBtn.onclick = (e) => {
        e.stopPropagation();
        if (+f.is_archived) {
          alert('Cannot rename archived folders.');
          return;
        }
        const nameSpan = d.querySelector('.folder-name');
        editFolderName({target: nameSpan}, f);
      };
    }
    if (deleteBtn) {
      deleteBtn.onclick = (e) => {
        e.stopPropagation();
        if (confirm('Delete this folder? This cannot be undone. Ensure no students are assigned to it.')) {
          fetch('delete_folder.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({section_id: f.id})
          })
          .then(r => r.json())
          .then(d => {
            if (d.success) {
              location.reload();
            } else {
              alert(d.message);
            }
          })
          .catch(e => alert('Network error'));
        }
      };
    }

    container.appendChild(d);
  });
  }

  function openFolder(id,name){
    currentSectionId = id;
    document.getElementById('folderView').classList.add('hidden');
    document.getElementById('studentView').classList.remove('hidden');
    document.getElementById('classTitle').innerText = name;
    loadStudentList();
  }

  function addFolder(){
    if(!currentProgramId || currentProgramId === 'archive'){
      alert('Select a program first');
      return;
    }

    fetch('add_folder.php',{
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body:JSON.stringify({
        program_id:+currentProgramId,
        name:document.getElementById('newFolderName').value.trim(),
        semester:document.getElementById('newSemester').value,
        is_archived:document.getElementById('newIsArchived').checked ? 1 : 0
      })
    })
    .then(r=>r.json())
    .then(d=>{
      alert(d.message);
      if(d.success) location.reload();
    })
    .catch(e=>alert('Network error'));
  }

function loadStudentList(){
  fetch(`studentlist.php?section_id=${currentSectionId}&json=1`)
    .then(r => {
      if (!r.ok) throw new Error('Network response was not ok');
      return r.json();
    })
    .then(d => {
      console.log('Fetched students:', d);
      const students = d.students || [];
      const t = document.getElementById('studentTable');
      t.innerHTML = '';
      if (students.length === 0) {
        t.innerHTML = '<tr><td colspan="8" class="text-center py-4">No students found in this section.</td></tr>';
      } else {
        students.forEach((s,i)=>{
          t.innerHTML += `
            <tr>
              <td>${i+1}</td>
              <td>${s.student_id||''}</td>
              <td>${s.name||''}</td>
              <td>${s.phone||''}</td>
              <td>${s.email||''}</td>
              <td>${s.requirements_completed==1?'Yes':'No'}</td>
              <td>${s.emergency_contact||''}</td>
              <td><a href="appointment_history.php?student_id=${encodeURIComponent(s.id)}" target="_blank">View</a></td>
            </tr>`;
        });
      }
    })
    .catch(e => {
      console.error('Error loading students:', e);
      alert('Error loading students: ' + e.message);
      const t = document.getElementById('studentTable');
      t.innerHTML = '<tr><td colspan="8" class="text-center py-4 text-red-500">Failed to load students. Please try again.</td></tr>';
    });
}

function editFolderName(event, folderData) {
  if (+folderData.is_archived) {
    alert('Cannot rename archived folders.');
    return;
  }

  const span = event.target;
  const originalName = span.textContent;
  const folderItem = span.closest('.folder-item');
  const titleDiv = span.parentElement;

  // Create input
  const input = document.createElement('input');
  input.type = 'text';
  input.value = originalName;
  input.className = 'rename-input';
  input.style.width = '100px'; // Adjust as needed
  input.focus();

  // Replace span with input
  span.style.display = 'none';
  titleDiv.insertBefore(input, span.nextSibling);

  function saveName() {
    const newName = input.value.trim();
    if (!newName || newName === originalName) {
      cancelEdit();
      return;
    }

    fetch('rename_folder.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({section_id: folderData.id, new_name: newName})
    })
    .then(r => r.json())
    .then(d => {
      if (d.success) {
        // Update local data
        updateLocalSectionName(folderData.id, newName);
        // Update UI
        span.textContent = newName;
        span.style.display = 'inline';
        input.remove();
        // Update title for openFolder if needed, but since reload on other actions, it's fine
      } else {
        alert(d.message);
        cancelEdit();
      }
    })
    .catch(e => {
      alert('Network error: ' + e.message);
      cancelEdit();
    });
  }

  function cancelEdit() {
    span.style.display = 'inline';
    input.remove();
  }

  input.onblur = saveName;
  input.onkeypress = (e) => {
    if (e.key === 'Enter') saveName();
    if (e.key === 'Escape') cancelEdit();
  };
}

function updateLocalSectionName(sectionId, newName) {
  for (let progId in sections) {
    for (let i = 0; i < sections[progId].length; i++) {
      if (sections[progId][i].id == sectionId) {
        sections[progId][i].name = newName;
        return;
      }
    }
  }
}

  // Sidebar functionality
  function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    sidebar.classList.toggle('translate-x-0');
    overlay.classList.toggle('hidden');
  }

  function closeSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    sidebar.classList.remove('translate-x-0');
    overlay.classList.add('hidden');
  }

  window.onload = ()=>{
    const ids = Object.keys(programs);
    if(ids.length) showProgram(programs[ids[0]], ids[0]);
  };

  // Event listeners for sidebar
  document.getElementById('menuBtn').addEventListener('click', toggleSidebar);
  document.getElementById('closeSidebar').addEventListener('click', closeSidebar);
  document.getElementById('sidebarOverlay').addEventListener('click', closeSidebar);
  </script>

  </body>
  </html>
  