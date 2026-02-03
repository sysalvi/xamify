import { app, BrowserWindow, globalShortcut, ipcMain, session } from 'electron'
import path from 'path'
import { fileURLToPath } from 'url'
import os from 'os'
import { exec } from 'child_process'

const __filename = fileURLToPath(import.meta.url)
const __dirname = path.dirname(__filename)

const isDev = !app.isPackaged
const devServerUrl = 'http://localhost:5173'
const examUrl = 'http://localhost:8000'

let landingWindow = null
let examWindow = null

const hardenWindow = (win) => {
  let lastKey = ''
  let lastTime = 0

  win.webContents.on('context-menu', (event) => event.preventDefault())
  win.webContents.on('before-input-event', (event, input) => {
    const key = (input.key || '').toLowerCase()
    const hasModifier = input.control || input.meta

    if (hasModifier && ['c', 'v', 'x', 'a'].includes(key)) {
      event.preventDefault()
    }

    if (!hasModifier) {
      return
    }

    if (key === 'x') {
      lastKey = 'x'
      lastTime = Date.now()
      return
    }

    if (key === 'a' && lastKey === 'x' && Date.now() - lastTime < 1500) {
      event.preventDefault()
      exitExam()
      lastKey = ''
      lastTime = 0
    }
  })
}

const getLocalIp = () => {
  const nets = os.networkInterfaces()
  for (const name of Object.keys(nets)) {
    for (const net of nets[name] || []) {
      if (net.family === 'IPv4' && !net.internal) {
        return net.address
      }
    }
  }
  return '0.0.0.0'
}

const toggleExplorer = (enabled) => {
  if (process.platform !== 'win32') return
  if (enabled) {
    exec('start explorer.exe')
  } else {
    exec('taskkill /F /IM explorer.exe')
  }
}

const registerShortcuts = () => {
  const shortcuts = [
    'Alt+Tab',
    'Alt+F4',
    'CommandOrControl+Esc',
    'F11',
    'F12',
    'CommandOrControl+Shift+I',
    'CommandOrControl+Shift+J',
  ]

  shortcuts.forEach((key) => {
    try {
      globalShortcut.register(key, () => {})
    } catch {
      // ignore
    }
  })

  let exitStepAt = 0
  globalShortcut.register('CommandOrControl+X', () => {
    exitStepAt = Date.now()
  })

  globalShortcut.register('CommandOrControl+A', () => {
    if (Date.now() - exitStepAt < 1500) {
      exitExam()
      exitStepAt = 0
    }
  })
}

const createLandingWindow = () => {
  if (landingWindow) return landingWindow

  landingWindow = new BrowserWindow({
    width: 520,
    height: 720,
    resizable: false,
    minimizable: false,
    maximizable: false,
    fullscreenable: false,
    title: 'XAMIFY Client',
    webPreferences: {
      preload: path.join(__dirname, 'preload.cjs'),
      contextIsolation: true,
      nodeIntegration: false,
    },
  })

  if (isDev) {
    landingWindow.loadURL(devServerUrl)
    landingWindow.webContents.openDevTools({ mode: 'detach' })
  } else {
    landingWindow.loadFile(path.join(__dirname, '../dist/index.html'))
  }

  hardenWindow(landingWindow)

  landingWindow.on('closed', () => {
    landingWindow = null
  })

  return landingWindow
}

const createExamWindow = () => {
  if (examWindow) return examWindow

  examWindow = new BrowserWindow({
    fullscreen: true,
    kiosk: true,
    alwaysOnTop: true,
    skipTaskbar: true,
    frame: false,
    webPreferences: {
      preload: path.join(__dirname, 'preload.cjs'),
      contextIsolation: true,
      nodeIntegration: false,
    },
  })

  examWindow.setMenuBarVisibility(false)
  examWindow.show()

  examWindow.loadURL(examUrl)
  if (isDev) {
    examWindow.webContents.openDevTools({ mode: 'detach' })
  }

  examWindow.webContents.on('did-fail-load', () => {
    examWindow.show()
    examWindow.loadURL('data:text/html;charset=utf-8,' + encodeURIComponent(`
      <html><body style="margin:0;font-family:Arial;background:#0f172a;color:#fff;display:flex;align-items:center;justify-content:center;height:100vh;">
      <div style="text-align:center;max-width:480px;">
      <h1 style="margin:0 0 8px;">Tidak bisa memuat ujian</h1>
      <p style="margin:0;opacity:0.8;">Pastikan server berjalan di http://localhost:8000</p>
      </div></body></html>
    `))
  })

  hardenWindow(examWindow)

  examWindow.on('closed', () => {
    examWindow = null
    toggleExplorer(true)
    globalShortcut.unregisterAll()
  })

  return examWindow
}

const setUserAgentHeader = () => {
  const ua = `${session.defaultSession.getUserAgent()} EXAMUQ-BROWSER/1.0`
  session.defaultSession.setUserAgent(ua)
  session.defaultSession.webRequest.onBeforeSendHeaders((details, callback) => {
    details.requestHeaders['User-Agent'] = ua
    callback({ cancel: false, requestHeaders: details.requestHeaders })
  })
}

const startExam = () => {
  console.log('[XAMIFY] startExam triggered')
  toggleExplorer(false)
  registerShortcuts()
  const win = createExamWindow()
  win.show()
  win.setFullScreen(true)
  win.setKiosk(true)
  setTimeout(() => {
    if (landingWindow) {
      landingWindow.hide()
    }
  }, 200)
}

const exitExam = () => {
  toggleExplorer(true)
  globalShortcut.unregisterAll()
  if (examWindow) {
    examWindow.close()
    examWindow = null
  }
  if (landingWindow) {
    landingWindow.show()
  } else {
    createLandingWindow()
  }
}

app.whenReady().then(() => {
  setUserAgentHeader()
  createLandingWindow()

  ipcMain.handle('exam:get-ip', () => getLocalIp())
  ipcMain.handle('exam:ping', () => 'pong')
  ipcMain.on('exam:start', () => {
    console.log('[XAMIFY] ipc exam:start')
    startExam()
  })
  ipcMain.on('exam:exit', () => {
    console.log('[XAMIFY] ipc exam:exit')
    exitExam()
  })
})

app.on('window-all-closed', () => {
  if (process.platform !== 'darwin') {
    app.quit()
  }
})

app.on('before-quit', () => {
  toggleExplorer(true)
  globalShortcut.unregisterAll()
})

app.on('activate', () => {
  if (!landingWindow) {
    createLandingWindow()
  }
})
