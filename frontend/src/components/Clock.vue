<template>
    <div class="clockin-out">
        <div class="timer">
          <h5>Hours Worked</h5>
          <h2> {{ formattedTime }}</h2>
        </div>
        <div class="clock-container">
            <div class="clock-in card">
                <h2>You are currently logged out</h2>
                <button @click="handleClockIn">➡Clock In</button>
                <h5 v-if="clockInTime">You are currently Clocked In</h5>
                <p v-if="clockInTime" class="clock-label">
                  🕕Clock-In Time : <strong> {{ clockInTime }}</strong>
                </p>
                <p v-if="clockInLocation" class="clock-label">
                  📍Location: <strong>{{ clockInLocation }}</strong>
                </p>
            </div>
            <div class="clock-out card">
                <h4>Clock Out</h4>
                <button @click="handleClockOut">Clock Out ➡</button>
                <h4 v-if="clockOutTime">You are currently Clocked Out</h4>
                <p v-if="clockOutTime" class="clock-label">
                  🕕Clock-Out Time : <strong> {{ clockOutTime }}</strong>
                </p>
                <p v-if="clockOutLocation" class="clock-label">
                  📍Location: <strong>{{ clockOutLocation }}</strong>
                </p>
                <p v-if="totalTimeWorked" class="worked-time">
                  Total Time Worked: <strong> {{ totalTimeWorked }}</strong>
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
  import { ref, onUnmounted } from 'vue';

  const elapsedTime = ref(0);
  const timerInterval = ref(null);
  const formattedTime = ref('00:00:00')
  const totalTimeWorked = ref('')
  const clockInTime = ref('')
  const clockOutTime = ref('')
  const clockInLocation = ref('')
  const clockOutLocation = ref('')

  const getLocation = () => {
   return new Promise((resolve, reject) => {
    if (!navigator.geolocation) {
      reject('Geolocation not supported')
    } else {
      navigator.geolocation.getCurrentPosition(
        async (position) => {
          const { latitude, longitude } = position.coords
          try {
            const response = await fetch(
              `https://nominatim.openstreetmap.org/reverse?lat=${latitude}&lon=${longitude}&format=json`
            )
            const data = await response.json()

            const road = data.address.road || ''
            const suburb = data.address.suburb || data.address.neighbourhood || ''
            const city = data.address.city || data.address.town ||data.address.villge || ''
            const country = data.address.country || ''

            const locationParts = [road, suburb, city, country].filter(Boolean)
            const fullAddress = locationParts.join(', ')
            resolve(fullAddress || 'Location found')
          } catch (error){
          resolve(`Lat: ${latitude.toFixed(4)}, Long: ${longitude.toFixed(4)}`)
          }
        },
        () => reject('Unable to retrieve location')
      )
    }
  })
  }
  
  const handleClockIn = async () => {
    if (timerInterval.value) return
    totalTimeWorked.value = ''
    clockOutTime.value = ''
    clockOutLocation.value = ''

    try {
      const location = await getLocation()
      clockInLocation.value = location 
    } catch (error) {
      clockInLocation.value = 'Location unavailable'
    }
    const now = new Date()
    clockInTime.value = now.toLocaleString()
    const start = Date.now() - elapsedTime.value * 1000
    timerInterval.value = setInterval(() => {
      elapsedTime.value = Math.floor((Date.now() - start) / 1000)
      formattedTime.value = formatTime(elapsedTime.value)
    }, 1000)
  }

  const handleClockOut = async () => {
    if (!timerInterval.value) return
    clearInterval(timerInterval.value)
    timerInterval.value = null
    const now = new Date()
    clockOutTime.value = now.toLocaleString()
    totalTimeWorked.value = formatTime(elapsedTime.value)

    elapsedTime.value = 0
    formattedTime.value = '00:00:00'

    try {
      const location = await getLocation()
      clockOutLocation.value = location 
    } catch (error) {
      clockOutLocation.value = 'Location unavailable'
    }
  }


  const startTimer = () => {
    if (timerInterval.value) return
    totalTimeWorked.value = ''
    const start = Date.now() - elapsedTime.value * 1000
    timerInterval.value = setInterval(() => {
      elapsedTime.value = Math.floor((Date.now() - start) / 1000)
      formattedTime.value = formatTime(elapsedTime.value)
    }, 1000)
  }

  const stopTimer = () => {
    if (!timerInterval.value) return
    clearInterval(timerInterval.value)
    timerInterval.value = null
    totalTimeWorked.value = formatTime(elapsedTime.value)
  }

  const formatTime = (seconds) => {
    const hrs = String(Math.floor(seconds / 3600)).padStart(2, '0')
    const mins = String(Math.floor((seconds % 3600) / 60)).padStart(2, '0')
    const secs = String(Math.floor(seconds % 60)).padStart(2, '0')
    return `${hrs}:${mins}:${secs}`
  }

  onUnmounted(() => {
    if (timerInterval.value) clearInterval(timerInterval.value)
  })

</script>

<style lang="scss" scoped>
.clockin-out {
  font-family: Poppins, sans-serif;
  background-color: rgb(235, 255, 253);
  min-height: 100vh;
  margin: 0;
  padding: 0;
}

/* ===== HEADER ===== */
header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #248A6C;
  padding: 15px 30px;
  flex-wrap: wrap; /* Allow wrapping on smaller screens */
  gap: 10px;

  .header-left {
    background: #fff;
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 10px 15px;
    border-radius: 10px;

    img {
      width: 80px;
      height: 80px;
      object-fit: cover;
      border-radius: 50%;
      border: 3px solid #4caf50;
    }

    h4 {
      margin: 0;
      font-size: 1.2rem;
      color: #333;
    }
  }

  .header-right {
    color: #fff;
    text-align: center;
    flex: 1;

    h1 {
      font-size: 1.8rem;
      margin-bottom: 5px;
    }
  }
}

/* ===== TIMER SECTION ===== */
.timer {
  text-align: center;
  font-weight: bold;
  color: #000000;
  margin: 10px 0;

  h5 {
    font-size: 1.2rem;
    margin-bottom: 5px;
  }

  h2 {
    font-size: 2.5rem;
    margin: 0;
  }
}

/* ===== CLOCK CARDS ===== */
.clock-container {
  display: flex;
  justify-content: center;
  gap: 15px;
  flex-wrap: wrap;
  margin-top: 10px;
  padding: 0 10px;

  .card {
    background: #fff;
    border-radius: 8px;
    padding: 15px;
    flex: 1 1 220px;
    text-align: center;
    border-left: 2px solid #000;
    border-bottom: 2px solid #000;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
    transition: transform 0.2s ease, box-shadow 0.2s ease;

    &:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 20px rgba(0, 0, 0, 0.25);
    }

    h4, h5 {
      margin-bottom: 10px;
      color: #000;
    }

    button {
      background-color: #248A6C;
      color: white;
      border: none;
      padding: 10px 20px;
      font-size: 1rem;
      border-radius: 4px;
      cursor: pointer;
      transition: background-color 0.3s ease;

      &:hover {
        background-color: #45A049;
      }
    }

    &.clock-out button {
      background-color: #248A6C;

      &:hover {
        background-color: #E53935;
      }
    }

    .clock-label {
      margin-top: 8px;
      font-size: 0.95rem;
      color: #000;
    }

    .worked-time {
      margin-top: 5px;
      font-size: 1rem;
      color: #000;
      font-weight: bold;
    }
  }
}

/* ======== RESPONSIVE DESIGN ======== */

/* Tablets (<= 992px) */
@media (max-width: 992px) {
  header {
    flex-direction: column;
    align-items: center;
    text-align: center;

    .header-left {
      flex-direction: column;
      img {
        width: 70px;
        height: 70px;
      }
    }

    .header-right h1 {
      font-size: 1.5rem;
    }
  }

  .timer h2 {
    font-size: 2rem;
  }

  .clock-container {
    gap: 10px;

    .card {
      flex: 1 1 45%;
    }
  }
}

/* Phones (<= 600px) */
@media (max-width: 600px) {
  header {
    flex-direction: column;
    padding: 10px 15px;

    .header-left {
      flex-direction: column;
      align-items: center;
      gap: 10px;

      img {
        width: 60px;
        height: 60px;
      }

      h4 {
        font-size: 1rem;
      }

      p {
        font-size: 0.85rem;
        text-align: center;
      }
    }

    .header-right h1 {
      font-size: 1.3rem;
      margin-top: 5px;
    }
  }

  .timer h2 {
    font-size: 1.8rem;
  }

  .clock-container {
    flex-direction: column;
    align-items: center;
    .card {
      width: 100%;
      max-width: 320px;
    }
  }
}

/* Very small screens (<= 400px) */
@media (max-width: 400px) {
  .header-left img {
    width: 50px;
    height: 50px;
  }

  .header-right h1 {
    font-size: 1.1rem;
  }

  .timer h2 {
    font-size: 1.5rem;
  }

  button {
    padding: 8px 16px;
    font-size: 0.9rem;
  }
}
</style>

