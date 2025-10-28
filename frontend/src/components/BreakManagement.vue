<template>
  <div class="break-container">
    <h1>Break Management</h1>
    <p>{{ currentDate }}</p>

    <div class="timer-card">
      <h2>Break Timer</h2>
      <h1>{{ formattedTime }}</h1>
      <p v-if="!isActive">No Active Break</p>
      <p v-else>Current: {{ currentBreakTypeLabel }}</p>
    </div>

    <!-- Dropdown Buttons -->
    <div class="buttons">
      <!-- Start Dropdown -->
      <div class="dropdown">
        <button class="dropbtn start-btn">Start Break ▼</button>
        <div class="dropdown-content">
          <a @click="startBreak('tea')">Start Tea</a>
          <a @click="startBreak('lunch')">Start Lunch</a>
        </div>
      </div>

      <!-- End Dropdown -->
      <div class="dropdown">
        <button class="dropbtn end-btn">End Break ▼</button>
        <div class="dropdown-content">
          <a @click="endBreak('tea')">End Tea Break</a>
          <a @click="endBreak('lunch')">End Lunch Break</a>
        </div>
      </div>
    </div>

    <div class="history">
      <h3>Today's Breaks</h3>
      <div v-if="breakHistory.length === 0">No recorded breaks yet.</div>
      <ul>
        <li v-for="(b, i) in breakHistory" :key="i">
          <strong>{{ b.typeLabel }}</strong>: {{ b.start }} → {{ b.end }} ({{ b.duration }})
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';

const isActive = ref(false);
const startTime = ref(null);
const timer = ref(0);
const interval = ref(null);
const activeBreakType = ref(null);
const breakHistory = ref([]);

const currentDate = new Date().toLocaleDateString('en-US', {
  weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
});

const formattedTime = computed(() => {
  const hrs = String(Math.floor(timer.value / 3600)).padStart(2, '0');
  const mins = String(Math.floor((timer.value % 3600) / 60)).padStart(2, '0');
  const secs = String(timer.value % 60).padStart(2, '0');
  return `${hrs}:${mins}:${secs}`;
});

const currentBreakTypeLabel = computed(() => {
  if (activeBreakType.value === 'tea') return 'Tea Break';
  if (activeBreakType.value === 'lunch') return 'Lunch Break';
  return 'Unknown Break';
});

function startBreak(type) {
  if (isActive.value) return alert('A break is already active!');
  activeBreakType.value = type;
  isActive.value = true;
  startTime.value = new Date();

  localStorage.setItem('activeBreakType', type);
  localStorage.setItem('breakStartTime', startTime.value);
  localStorage.setItem('breakActive', true);

  interval.value = setInterval(() => {
    timer.value = Math.floor((new Date() - new Date(startTime.value)) / 1000);
  }, 1000);
}

function endBreak(type) {
  if (!isActive.value || activeBreakType.value !== type) {
    return alert(`No active ${type} break to end!`);
  }

  const endTime = new Date();
  const durationMinutes = Math.floor(timer.value / 60);
  const typeLabel = type === 'tea' ? 'Tea Break' : 'Lunch Break';

  const breakData = {
    type,
    typeLabel,
    start: startTime.value.toLocaleTimeString(),
    end: endTime.toLocaleTimeString(),
    duration: `${durationMinutes} min`,
  };

  breakHistory.value.push(breakData);
  localStorage.setItem('breakHistory', JSON.stringify(breakHistory.value));

  clearInterval(interval.value);
  isActive.value = false;
  timer.value = 0;
  startTime.value = null;
  activeBreakType.value = null;
  localStorage.removeItem('activeBreakType');
  localStorage.removeItem('breakStartTime');
  localStorage.removeItem('breakActive');

  saveBreakToBackend(breakData);
}

function saveBreakToBackend(breakData) {
  fetch(`http://localhost:8090/api/breaks/${breakData.type}`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(breakData),
  }).catch(err => console.error('Backend not connected:', err));
}

onMounted(() => {
  const active = localStorage.getItem('breakActive');
  const storedStart = localStorage.getItem('breakStartTime');
  const storedType = localStorage.getItem('activeBreakType');
  const storedHistory = JSON.parse(localStorage.getItem('breakHistory')) || [];

  breakHistory.value = storedHistory;

  if (active && storedStart && storedType) {
    isActive.value = true;
    activeBreakType.value = storedType;
    startTime.value = new Date(storedStart);
    interval.value = setInterval(() => {
      timer.value = Math.floor((new Date() - new Date(startTime.value)) / 1000);
    }, 1000);
  }
});

onUnmounted(() => {
  if (interval.value) clearInterval(interval.value);
});
</script>

<style scoped>
.break-container {
  margin-top: 40px;
  text-align: center;
}

.timer-card {
  background: #e8f9f0;
  border-radius: 12px;
  padding: 25px;
  display: inline-block;
  min-width: 300px;
}

.buttons {
  margin: 25px;
  display: flex;
  justify-content: center;
  gap: 15px;
}

/* Dropdown styles */
.dropdown {
  position: relative;
  display: inline-block;
}

.dropbtn {
  border: none;
  border-radius: 8px;
  padding: 10px 20px;
  font-weight: bold;
  cursor: pointer;
}

.start-btn {
  background-color: #4caf50;
  color: white;
}

.end-btn {
  background-color: #e74c3c;
  color: white;
}

.dropdown-content {
  display: none;
  position: absolute;
  background-color: #fff;
  box-shadow: 0px 8px 16px rgba(0,0,0,0.1);
  border-radius: 8px;
  min-width: 160px;
  z-index: 1;
}

.dropdown-content a {
  color: black;
  padding: 10px 16px;
  display: block;
  text-decoration: none;
}

.dropdown-content a:hover {
  background-color: #f1f1f1;
}

.dropdown:hover .dropdown-content {
  display: block;
}

.history {
  margin-top: 20px;
  background: #f1f1f1;
  display: inline-block;
  padding: 15px;
  border-radius: 10px;
  text-align: left;
}
</style>
