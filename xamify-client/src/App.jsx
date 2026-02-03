import { useEffect, useState } from 'react'

const EXIT_SEQUENCE = ['Control', 'x', 'a']

function App() {
  const [ipAddress, setIpAddress] = useState('0.0.0.0')
  const [sequence, setSequence] = useState([])
  const [clicked, setClicked] = useState(false)
  const [bridgeStatus, setBridgeStatus] = useState('checking')
  const isExamMode = new URLSearchParams(window.location.search).get('mode') === 'exam'

  useEffect(() => {
    if (window.examAPI?.getLocalIp) {
      window.examAPI.getLocalIp().then((ip) => {
        if (ip) setIpAddress(ip)
      })
      window.examAPI
        .ping?.()
        .then((res) => setBridgeStatus(res === 'pong' ? 'ok' : 'error'))
        .catch(() => setBridgeStatus('error'))
    } else {
      setBridgeStatus('missing')
    }
  }, [])

  useEffect(() => {
    const handleKeyDown = (event) => {
      const key = event.key.length === 1 ? event.key.toLowerCase() : event.key
      setSequence((prev) => {
        const next = [...prev, key].slice(-3)
        const match = EXIT_SEQUENCE.every((k, idx) => next[idx] === k)
        if (match) {
          window.examAPI?.exitExam?.()
        }
        return next
      })
    }

    window.addEventListener('keydown', handleKeyDown)
    return () => window.removeEventListener('keydown', handleKeyDown)
  }, [])

  const handleStart = () => {
    console.log('[XAMIFY] start button clicked')
    setClicked(true)
    window.examAPI?.startExam?.()
  }

  return (
    <div className="flex min-h-screen items-center justify-center bg-slate-50 px-6 py-10">
      <div className="w-full max-w-md rounded-[32px] bg-white px-8 py-10 shadow-[0_30px_60px_rgba(15,23,42,0.12)]">
        <div className="mx-auto flex h-20 w-20 items-center justify-center rounded-full border-2 border-brandOrange/30 bg-brandOrange/10 text-3xl">
          💻
        </div>
        <h1 className="mt-6 text-center text-3xl font-bold text-slate-900">Selamat Datang</h1>
        <p className="mt-2 text-center text-sm text-slate-500">
          Pastikan IP Anda sesuai dengan jaringan ujian.
        </p>

        <div className="mt-8 rounded-2xl border border-slate-200 bg-slate-50 px-6 py-5 text-center">
          <p className="text-xs font-semibold tracking-[0.3em] text-slate-400">IP ADDRESS ANDA</p>
          <p className="mt-3 text-2xl font-semibold text-slate-900">{ipAddress}</p>
        </div>

        {!isExamMode ? (
          <button
            onClick={handleStart}
            className="mt-8 w-full rounded-2xl bg-brandOrange px-6 py-4 text-base font-semibold text-white shadow-glow transition hover:scale-[1.01]"
          >
            MULAI UJIAN SEKARANG →
          </button>
        ) : (
          <div className="mt-8 rounded-2xl border border-brandOrange/20 bg-brandOrange/10 px-4 py-3 text-center text-sm font-semibold text-brandOrange">
            Mode Ujian Aktif
          </div>
        )}

        {clicked && (
          <p className="mt-3 text-center text-xs text-slate-400">Memulai ujian...</p>
        )}
        {bridgeStatus !== 'ok' && (
          <p className="mt-3 text-center text-xs text-red-500">
            Bridge error: {bridgeStatus}
          </p>
        )}

        <p className="mt-10 text-center text-xs font-semibold uppercase tracking-[0.3em] text-slate-300">
          XAMIFY CLIENT V2.4
        </p>
      </div>
    </div>
  )
}

export default App
