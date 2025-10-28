<template>
  <div class="min-h-screen bg-white flex flex-col items-center justify-start p-6">
    <!-- Header -->
    <header class="w-full flex justify-between items-center bg-green-700 text-white px-8 py-4 shadow">
      <h1 class="text-2xl font-bold">☕ CLOCK IT</h1>
      <nav class="space-x-6">
        <button class="hover:underline">Home</button>
        <button class="underline font-semibold">Break</button>
        <button class="hover:underline">Clock in</button>
      </nav>
    </header>

    <!-- Main Content -->
    <main class="max-w-xl w-full mt-10 bg-gray-50 rounded-2xl shadow p-6 text-center">
      <h2 class="text-2xl font-semibold mb-2">Break Management</h2>
      <p class="text-gray-500 mb-6">{{ todayDate }}</p>

      <h3 class="text-xl font-semibold mb-4">Tea Break</h3>

      <div class="bg-green-50 border border-green-200 rounded-xl p-6 mb-6">
        <p class="text-xl font-mono mb-2">Break Timer</p>
        <h1 class="text-4xl font-bold mb-2">{{ formattedTime }}</h1>
        <p class="text-gray-500">{{ activeBreak ? "Active Break" : "No Active Break" }}</p>
      </div>

      <div class="flex justify-center space-x-4 mb-6">
        <button
          @click="startTea"
          :disabled="activeBreak"
          class="bg-green-400 hover:bg-green-500 text-white px-6 py-2 rounded-lg disabled:opacity-40"
        >
          Start Tea
        </button>
        <button
          @click="endTea"
          :disabled="!activeBreak"
          class="bg-red-400 hover:bg-red-500 text-white px-6 py-2 rounded-lg disabled:opacity-40"
        >
          End Tea
        </button>
      </div>

      <div class="bg-green-100 p-4 rounded-xl text-left">
        <h4 class="font-semibold mb-2">Today's Breaks</h4>
        <ul>
          <li
            v-for="(b, index) in breaks"
            :key="index"
            class="border-b border-green-200 py-2"
          >
            <strong>{{ b.type }}</strong><br />
            {{ b.start }} to {{ b.end }} ({{ b.duration }})
          </li>
          <li v-if="breaks.length === 0" class="text-gray-500 italic">No breaks yet</li>
        </ul>
      </div>
    </main>

    <!-- Footer -->
    <footer class="mt-auto text-center text-sm text-gray-400 py-6">
      ©2025 Clockit
    </footer>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from "vue";

const activeBreak = ref(false);
const startTime = ref(null);
const elapsed = ref(0);
const timerInterval = ref(null);
const breaks = ref([]);

// --- Get today's date ---
const todayDate = new Date().toLocaleDateString("en-US", {
  weekday: "long",
  year: "numeric",
  month: "long",
  day: "numeric",
});

// --- Format timer display ---
const formattedTime = computed(() => {
  const hours = String(Math.floor(elapsed.value / 3600)).padStart(2, "0");
  const minutes = String(Math.floor((elapsed.value % 3600) / 60)).padStart(2, "0");
  const seconds = String(elapsed.value % 60).padStart(2, "0");
  return `${hours}:${minutes}:${seconds}`;
});

// --- Save to LocalStorage ---
function saveToLocalStorage() {
  const data = {
    activeBreak: activeBreak.value,
    startTime: startTime.value ? startTime.value.toISOString() : null,
    breaks: breaks.value,
  };
  localStorage.setItem("breakManagerData", JSON.stringify(data));
}

// --- Load from LocalStorage ---
function loadFromLocalStorage() {
  const saved = localStorage.getItem("breakManagerData");
  if (saved) {
    const data = JSON.parse(saved);
    breaks.value = data.breaks || [];
    if (data.activeBreak && data.startTime) {
      activeBreak.value = true;
      startTime.value = new Date(data.startTime);
      const now = new Date();
      elapsed.value = Math.floor((now - startTime.value) / 1000);
      resumeTimer();
    }
  }
}

// --- Start Timer ---
function startTimer() {
  timerInterval.value = setInterval(() => {
    const now = new Date();
    elapsed.value = Math.floor((now - startTime.value) / 1000);
  }, 1000);
}

// --- Resume Timer (after refresh) ---
function resumeTimer() {
  clearInterval(timerInterval.value);
  startTimer();
}

// --- Start Tea Break ---
function startTea() {
  if (activeBreak.value) return;
  activeBreak.value = true;
  startTime.value = new Date();
  elapsed.value = 0;

  startTimer();
  saveToLocalStorage();

  // Placeholder for backend integration:
  // await fetch('/api/breaks/start', {
  //   method: 'POST',
  //   headers: { 'Content-Type': 'application/json' },
  //   body: JSON.stringify({ employee_id, type: 'Tea Break', start_time: startTime.value })
  // })
}

// --- End Tea Break ---
function endTea() {
  if (!activeBreak.value) return;
  clearInterval(timerInterval.value);
  activeBreak.value = false;
  const endTime = new Date();

  const durationMs = endTime - startTime.value;
  const durationMin = Math.round(durationMs / 60000);

  breaks.value.push({
    type: "Tea Break",
    start: startTime.value.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" }),
    end: endTime.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" }),
    duration: `${durationMin} min`,
  });

  startTime.value = null;
  elapsed.value = 0;
  saveToLocalStorage();

  // Placeholder for backend integration:
  // await fetch('/api/breaks/end', {
  //   method: 'POST',
  //   headers: { 'Content-Type': 'application/json' },
  //   body: JSON.stringify({ employee_id, end_time: endTime, duration: durationMin })
  // })
}

// --- Lifecycle Hooks ---
onMounted(() => {
  loadFromLocalStorage();
});

onUnmounted(() => {
  if (timerInterval.value) clearInterval(timerInterval.value);
});
</script>

<style scoped>
body {
  font-family: "Inter", sans-serif;
}
</style>
