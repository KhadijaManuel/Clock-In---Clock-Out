<template>
  <div class="clock-in-container">
    <div class="desktop-header">Desktop - 1</div>
    
    <div class="clock-in-card">
      <div class="header">
        <h1>Clock In</h1>
        <button class="close-btn" @click="handleClose">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="15" y1="9" x2="9" y2="15"></line>
            <line x1="9" y1="9" x2="15" y2="15"></line>
          </svg>
        </button>
      </div>

      <div class="content">
        <button v-if="!showScanner" class="clock-in-btn" @click="handleManualClockIn">
          Clock in
        </button>

        <div class="scanner-section" :class="{ 'scanner-active': showScanner }">
          <button class="qr-scan-btn" @click="toggleScanner" :disabled="showScanner">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="3" y="3" width="7" height="7"></rect>
              <rect x="14" y="3" width="7" height="7"></rect>
              <rect x="14" y="14" width="7" height="7"></rect>
              <rect x="3" y="14" width="7" height="7"></rect>
            </svg>
            QR Code Scan
          </button>

          <template v-if="showScanner">
            <p class="instruction">Align the QR code within the frame to clock in.</p>

            <div class="scanner-preview">
              <div v-if="!scannerReady" class="scanner-placeholder">
                <div class="scan-frame">
                  <svg class="scan-icon" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 3H5a2 2 0 0 0-2 2v4"></path>
                    <path d="M15 3h4a2 2 0 0 1 2 2v4"></path>
                    <path d="M9 21H5a2 2 0 0 1-2-2v-4"></path>
                    <path d="M15 21h4a2 2 0 0 0 2-2v-4"></path>
                  </svg>
                </div>
                <p class="status-text">Ready to Scan</p>
              </div>
              <div id="qr-reader" v-show="scannerReady"></div>
            </div>

            <transition name="fade">
              <div v-if="message" :class="['message', statusClass]">
                <img
                  v-if="statusClass === 'error'"
                  :src="errorIcon"
                  alt="error icon"
                  class="icon"
                />
                {{ message }}
              </div>
            </transition>

            <div class="action-buttons">
              <button class="manual-clockin-btn" @click="handleManualClockIn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <circle cx="12" cy="12" r="10"></circle>
                  <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
                Clock In Manually
              </button>
              <button class="cancel-btn" @click="cancelScanning">
                Cancel
              </button>
            </div>
          </template>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { Html5QrcodeScanner, Html5QrcodeScanType } from "html5-qrcode";
import axios from "axios";
import successSound from "@/assets/success.mp3";
import errorSound from "@/assets/error.mp3";
import errorIcon from "@/assets/icons8-error-48.png";

export default {
  name: 'ClockInScanner',
  data() {
    return {
      showScanner: true,
      scannerReady: false,
      message: '',
      statusClass: '',
      errorIcon,
      scanner: null
    }
  },
  mounted() {
    this.initScanner();
  },
  beforeUnmount() {
    if (this.scanner) {
      this.scanner.clear().catch(error => {
        console.error("Failed to clear scanner", error);
      });
    }
  },
  methods: {
    handleClose() {
      console.log('Close clicked');
      this.$emit('close');
    },
    
    async handleManualClockIn() {
      try {
        // Simulate API call for manual clock-in
        const response = await axios.post("http://localhost:3000/clock-in/manual", {
          timestamp: new Date().toISOString(),
          type: 'manual'
        });

        if (response.data.success) {
          this.showMessage(`✅ ${response.data.message || 'Clocked in successfully!'}`, "success");
          
          // Close scanner if open
          if (this.showScanner) {
            setTimeout(() => {
              this.cancelScanning();
            }, 2000);
          }
        } else {
          this.showMessage(`❌ ${response.data.message || 'Failed to clock in'}`, "error");
        }
      } catch (err) {
        this.showMessage("❌ Error: Could not connect to the server!", "error");
      }
    },

    toggleScanner() {
      if (!this.showScanner) {
        this.initScanner();
      }
    },

    async initScanner() {
      this.showScanner = true;
      this.message = '';
      this.statusClass = '';
      this.scannerReady = false;

      await this.$nextTick();

      this.scanner = new Html5QrcodeScanner("qr-reader", {
        fps: 10,
        qrbox: 250,
        rememberLastUsedCamera: true,
        supportedScanTypes: [Html5QrcodeScanType.SCAN_TYPE_CAMERA],
      });

      this.scanner.render(
        async (decodedText) => {
          try {
            const response = await axios.post("http://localhost:3000/scan", {
              qrData: decodedText,
            });

            if (response.data.success) {
              this.showMessage(`✅ ${response.data.message}`, "success");
              
              setTimeout(() => {
                this.cancelScanning();
              }, 2000);
            } else {
              this.showMessage(`❌ ${response.data.message}`, "error");
            }
          } catch (err) {
            this.showMessage("❌ API Error: Could not connect to the server!", "error");
          }
        },
        (error) => {
          console.warn(`Scan error: ${error}`);
        }
      );

      setTimeout(() => {
        this.scannerReady = true;
      }, 500);
    },

    async cancelScanning() {
      if (this.scanner) {
        try {
          await this.scanner.clear();
          this.scanner = null;
        } catch (error) {
          console.error("Failed to clear scanner", error);
        }
      }
      this.showScanner = false;
      this.scannerReady = false;
      this.message = '';
      this.statusClass = '';
    },

    showMessage(text, type) {
      this.message = text;
      this.statusClass = type;

      const sound = new Audio(type === "success" ? successSound : errorSound);
      sound.play().catch(err => console.log("Audio play failed:", err));

      setTimeout(() => {
        this.message = "";
        this.statusClass = "";
      }, 3000);
    }
  }
}
</script>

<style scoped>
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

.clock-in-container {
  min-height: 100vh;
  background-color: #2b2b2b;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 20px;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
  position: relative;
}

.desktop-header {
  position: absolute;
  top: 20px;
  left: 20px;
  color: #999;
  font-size: 14px;
  font-weight: 400;
}

.clock-in-card {
  width: 100%;
  max-width: 656px;
  background-color: white;
  border-radius: 0;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.header {
  background-color: #4ec9a7;
  color: white;
  padding: 18px 24px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.header h1 {
  font-size: 22px;
  font-weight: 500;
  letter-spacing: 0.3px;
}

.close-btn {
  background: none;
  border: none;
  color: white;
  cursor: pointer;
  padding: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: opacity 0.2s;
}

.close-btn:hover {
  opacity: 0.8;
}

.content {
  padding: 28px 32px 40px;
  display: flex;
  flex-direction: column;
  gap: 0;
  min-height: 200px;
}

.clock-in-btn {
  align-self: flex-end;
  background-color: white;
  color: #4ec9a7;
  border: 1.5px solid #4ec9a7;
  padding: 9px 32px;
  border-radius: 4px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  margin-bottom: 20px;
}

.clock-in-btn:hover {
  background-color: #4ec9a7;
  color: white;
}

.scanner-section {
  background-color: #dff0eb;
  padding: 28px 32px;
  border-radius: 6px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 20px;
}

.scanner-section.scanner-active {
  gap: 22px;
}

.qr-scan-btn {
  background-color: white;
  color: #333;
  border: 1.5px solid #b8b8b8;
  padding: 10px 22px;
  border-radius: 4px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 10px;
  transition: all 0.2s;
  align-self: center;
}

.qr-scan-btn:hover:not(:disabled) {
  border-color: #4ec9a7;
  background-color: #f9fffe;
}

.qr-scan-btn:disabled {
  opacity: 1;
  cursor: default;
  background-color: white;
}

.instruction {
  color: #2a2a2a;
  font-size: 14px;
  text-align: center;
  margin: 0;
  line-height: 1.5;
  font-weight: 400;
}

.scanner-preview {
  width: 100%;
  max-width: 390px;
  aspect-ratio: 1;
  border-radius: 6px;
  overflow: hidden;
  background-color: #999999;
  display: flex;
  align-items: center;
  justify-content: center;
}

.scanner-placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 28px;
  padding: 40px;
}

.scan-frame {
  display: flex;
  align-items: center;
  justify-content: center;
}

.scan-icon {
  color: #555;
  width: 64px;
  height: 64px;
}

.status-text {
  color: #2a2a2a;
  font-size: 15px;
  font-weight: 500;
  margin: 0;
}

#qr-reader {
  width: 100%;
  height: 100%;
}

#qr-reader :deep(video) {
  border-radius: 0 !important;
}

#qr-reader :deep(#html5-qrcode-button-camera-permission),
#qr-reader :deep(#html5-qrcode-button-camera-start),
#qr-reader :deep(#html5-qrcode-button-camera-stop) {
  background-color: #4ec9a7 !important;
  border: none !important;
  color: white !important;
  padding: 10px 20px !important;
  border-radius: 4px !important;
  font-size: 14px !important;
  cursor: pointer !important;
  margin: 10px 5px !important;
}

#qr-reader :deep(select) {
  padding: 8px !important;
  border-radius: 4px !important;
  border: 1.5px solid #b8b8b8 !important;
  margin: 10px 5px !important;
  font-size: 14px !important;
}

.message {
  padding: 14px 20px;
  border-radius: 6px;
  font-size: 14px;
  text-align: center;
  width: 100%;
  max-width: 390px;
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
}

.icon {
  width: 22px;
  height: 22px;
  flex-shrink: 0;
}

.success {
  background: #c8e6c9;
  color: #1b5e20;
  border: 2px solid #81c784;
}

.error {
  background: #ffcdd2;
  color: #b71c1c;
  border: 2px solid #ef9a9a;
}

.action-buttons {
  display: flex;
  flex-direction: column;
  gap: 12px;
  width: 100%;
  max-width: 280px;
}

.manual-clockin-btn {
  background-color: white;
  color: #666;
  border: 1.5px solid #b8b8b8;
  padding: 10px 20px;
  border-radius: 4px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.manual-clockin-btn:hover {
  background-color: #f5f5f5;
  border-color: #666;
}

.cancel-btn {
  background-color: white;
  color: #4ec9a7;
  border: 1.5px solid #4ec9a7;
  padding: 10px 20px;
  border-radius: 4px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.cancel-btn:hover {
  background-color: #4ec9a7;
  color: white;
}

/* Fade transition */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.5s;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

@media (max-width: 640px) {
  .desktop-header {
    display: none;
  }

  .clock-in-card {
    max-width: 100%;
  }

  .content {
    padding: 24px 20px 32px;
  }

  .scanner-section {
    padding: 24px 20px;
  }
  
  .scanner-preview {
    max-width: 100%;
  }

  .message {
    max-width: 100%;
    font-size: 13px;
  }

  .action-buttons {
    max-width: 100%;
  }
}
</style>