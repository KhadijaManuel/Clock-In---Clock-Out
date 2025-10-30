<template>
  <div :class="['app-container',theme]">
    <div class="actions">
      <button class="theme-toogle" @click="toogleTheme">
        <span v-if="theme === 'light'">🌙</span>
        <span v-else>☀</span>
      </button>
    </div>
    <main>
      <h2 class="hours-title">Hours Worked</h2>
      <h1 class="timer-display">{{ formattedTime }}</h1>

      <div class="cards">
        <div class="card">
          <h2>
            {{ isClockedIn ? 'You are currently Clocked In' : 'You are currently Clocked Out' }}
          </h2>
          <button class="clock-btn" @click="handleClockIn" :disabled="isClockedIn">➡ Clock In</button>

          <h4 v-if="clockInTime" class="clock-label">
            🕕 Clock-In Time: <strong>{{ clockInTime }}</strong>
          </h4>
          <h4 v-if="clockInLocation" class="clock-label">
            📍 Location: <strong>{{ clockInLocation }}</strong>
          </h4>
        </div>

        <div class="card">
          <h2>
            {{ isClockedIn ? 'You are currently Clocked In' : 'You are currently Clocked Out' }}
          </h2>
          <button class="clock-btn" @click="handleClockOut" :disabled="!isClockedIn">Clock Out ➡</button>

          <p v-if="clockOutTime" class="clock-label">
            🕕 Clock-Out Time: <strong>{{ clockOutTime }}</strong>
          </p>
          <p v-if="clockOutLocation" class="clock-label">
            📍 Location: <strong>{{ clockOutLocation }}</strong>
          </p>
          <p v-if="totalTimeWorked" class="worked-time">
            Total Time Worked: <strong>{{ totalTimeWorked }}</strong>
          </p>
        </div>
      </div>
      <div class="hours-section">
        <h3>Hours Worked</h3>
        <p><strong>Day 20 - 5h</strong></p>
        <p><strong>Day 30 - 3h</strong></p>
      </div>
      <div id="map"></div>
    </main>
  </div>
</template>

<script setup>
import { ref, onUnmounted, nextTick } from "vue";
import 'leaflet/dist/leaflet.css';

const isClockedIn = ref(false);
const elapsedTime = ref(0);
const timerInterval = ref(null);
const formattedTime = ref("00:00:00");
const totalTimeWorked = ref("");
const clockInTime = ref("");
const clockOutTime = ref("");
const clockInLocation = ref("");
const clockOutLocation = ref("");
const theme = ref("light");

const toogleTheme = () => {
  theme.value = theme.value === "light" ? "dark" : "light";
};

const getLocation = () => {
  const latitude = -34.03;
  const longitude = 18.6;
  const locationName = "Imam Haron Road, Lansdowne, Cape Town, South Africa"; 
  return Promise.resolve(
    `${locationName}`
  );
};
// const getLocation = async () => {
//   const latitude = -33.9860846;
//   const longitude = 18.4932193;

//   try {
//     const response = await fetch(
//       `https://nominatim.openstreetmap.org/reverse?lat=${latitude}&lon=${longitude}&format=json`
//     );
//     const data = await response.json();
//     const road = data.address.road || "";
//     const suburb = data.address.suburb || data.address.neighbourhood || "";
//     const city = data.address.city || data.address.town || data.address.village || "";
//     const country = data.address.country || "";
//     const locationParts = [road, suburb, city, country].filter(Boolean);
//     const fullAddress = locationParts.join(", ");
//     return fullAddress || `Lat: ${latitude.toFixed(4)}, Long: ${longitude.toFixed(4)}`;
//   } catch (error) {
//     resolve(`Lat: ${latitude.toFixed(4)}, Long: ${longitude.toFixed(4)}`);
//   }
// };


const handleClockIn = async () => {
  if (timerInterval.value) return;
  totalTimeWorked.value = "";
  clockOutTime.value = "";
  clockOutLocation.value = "";

  try {
    const location = await getLocation();
    clockInLocation.value = location;
  } catch (error) {
    clockInLocation.value = "Location unavailable";
  }

  const now = new Date();
  clockInTime.value = now.toLocaleString();

  const start = Date.now() - elapsedTime.value * 1000;
  timerInterval.value = setInterval(() => {
    elapsedTime.value = Math.floor((Date.now() - start) / 1000);
    formattedTime.value = formatTime(elapsedTime.value);
  }, 1000);

  isClockedIn.value = true;
};

const handleClockOut = async () => {
  if (!timerInterval.value) return;
  clearInterval(timerInterval.value);
  timerInterval.value = null;

  const now = new Date();
  clockOutTime.value = now.toLocaleString();
  totalTimeWorked.value = formatTime(elapsedTime.value);

  elapsedTime.value = 0;
  formattedTime.value = "00:00:00";

  try {
    const location = await getLocation();
    clockOutLocation.value = location;
  } catch (error) {
    clockOutLocation.value = "Location unavailable";
  }

  isClockedIn.value = false;

  await nextTick();
};

const startTimer = () => {
  if (timerInterval.value) return;
  totalTimeWorked.value = "";
  const start = Date.now() - elapsedTime.value * 1000;
  timerInterval.value = setInterval(() => {
    elapsedTime.value = Math.floor((Date.now() - start) / 1000);
    formattedTime.value = formatTime(elapsedTime.value);
  }, 1000);
};

const stopTimer = () => {
  if (!timerInterval.value) return;
  clearInterval(timerInterval.value);
  timerInterval.value = null;
  totalTimeWorked.value = formatTime(elapsedTime.value);
};

const formatTime = (seconds) => {
  const hrs = String(Math.floor(seconds / 3600)).padStart(2, "0");
  const mins = String(Math.floor((seconds % 3600) / 60)).padStart(2, "0");
  const secs = String(Math.floor(seconds % 60)).padStart(2, "0");
  return `${hrs}:${mins}:${secs}`;
};


onUnmounted(() => {
  if (timerInterval.value) clearInterval(timerInterval.value);
});
</script>

<style scoped lang="scss">
.app-container {
  min-height: 100vh;
  font-family: "Poppins", sans-serif;
  transition: background-color 0.3s ease, color 0.3s ease;
  &.light {
    background-color: #E8FFF9;
    color: #000;
  }
  &.dark {
    background-color: #1E1E1E;
    color: #F2F2F2;
  }
}
/* Navbar */
.navbar {
  background: #248A6C;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 20px;
  color: #fff;
  .logo {
    display: flex;
    align-items: center;
    gap: 10px;
    img {
      width: 35px;
      height: 35px;
    }
    h3 {
      font-weight: bold;
      font-size: 1.2rem;
    }
  }
  nav {
    display: flex;
    gap: 20px;
    a {
      color: #fff;
      text-decoration: none;
      font-weight: 500;
      &:hover {
        text-decoration: underline;
      }
    }
  }
  .actions {
    display: flex;
    align-items: center;
    gap: 10px;
    .theme-toggle {
      background: none;
      border: none;
      font-size: 1.4rem;
      cursor: pointer;
      color: #fff;
    }
    .logout {
      background: #fff;
      color: #248A6C;
      border: none;
      border-radius: 6px;
      padding: 6px 14px;
      cursor: pointer;
      font-weight: 600;
      &:hover {
        background: #C2F7E2;
      }
    }
  }
}
/* Timer */
.hours-title {
  text-align: center;
  margin-top: 20px;
}
.timer-display {
  text-align: center;
  font-size: 2.4rem;
  font-weight: bold;
  margin-bottom: 20px;
}

.cards {
  display: flex;
  justify-content: center;
  flex-wrap: wrap;
  gap: 20px;

  .card {
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
    padding: 20px;
    text-align: center;
    width: 400px;
    border-left: 3px solid #248A6C;
    transition: transform 0.2s ease;
    font-family: "Poppins", sans-serif; 
    font-weight: 500; 
    color: #000; 


    h2 {
      font-size: 2.1rem;
      margin-bottom: 10px;
      font-family: inherit;
      font-weight: 900;
      color: inherit;
    }

    h4, p, strong {
      font-family: inherit;
      font-weight: 700;
      color: inherit;
    }

    .location {
      margin: 10px 0;
    }

    .clock-btn {
      background: #248A6C;
      color: #fff;
      border: none;
      width: 250px;
      padding: 8px 20px;
      border-radius: 6px;
      cursor: pointer;

      
    }
  }
}


.break-hours {
  display: flex;
  justify-content: center;
  flex-wrap: wrap;
  gap: 30px;
  margin-top: 30px;
  .hours-section {
    text-align: center;
}
}
.dark {
  .navbar {
    background: #333;
  }
  .card {
    font-family: "Poppins", sans-serif;
    font-weight: 500;
    background: #2B2B2B;
    color: #fff;
    border-left-color: #4EE2AE;
  }
  .clock-btn {
    background: #4EE2AE;
    color: #1E1E1E;
  }
  .logout {
    background: #4EE2AE !important;
    color: #000 !important;
  }
}
</style>

