import { useState } from 'react'
import './CompanyWizard.css'

const STEPS = [
  { id: 1, label: 'Basic Info', icon: '🏢' },
  { id: 2, label: 'Address', icon: '📍' },
  { id: 3, label: 'Contact', icon: '📞' },
  { id: 4, label: 'Branding', icon: '🌿' },
  { id: 5, label: 'Finance', icon: '💰' },
  { id: 6, label: 'User Setup', icon: '👥' },
  { id: 7, label: 'Notifications', icon: '🔔' },
]

const MONTHS = [
  'January', 'February', 'March', 'April', 'May', 'June',
  'July', 'August', 'September', 'October', 'November', 'December',
]

const CURRENCIES = ['USD', 'TZS', 'EUR', 'GBP', 'KES', 'UGX', 'RWF', 'ZAR']

const INDUSTRIES = ['Tourism', 'Travel', 'Hospitality', 'Safari', 'Adventure', 'Airline', 'Other']

const BUSINESS_TYPES = ['Ltd', 'Sole Proprietor', 'Partnership', 'NGO', 'Association', 'Public Company']

const TENANT_ROLES = [
  'Company Admin', 'Finance Manager', 'Sales Manager', 'Operations Manager',
  'Fleet Manager', 'Reservations Officer', 'Accountant', 'Driver / Guide', 'Viewer',
]

const NOTIFY_EVENTS = [
  { key: 'new_lead', label: 'New Lead' },
  { key: 'quote_sent', label: 'Quote Sent' },
  { key: 'booking_confirmed', label: 'Booking Confirmed' },
  { key: 'payment_received', label: 'Payment Received' },
]

function Field({ label, required, children, full }) {
  return (
    <label className={`wiz-field ${full ? 'wiz-field-full' : ''}`}>
      <span className="wiz-field-label">{label}{required ? <em>*</em> : null}</span>
      {children}
    </label>
  )
}

function CompanyWizard({ apiBaseUrl, apiToken, onClose, onCreated }) {
  const [step, setStep] = useState(1)
  const [isSaving, setIsSaving] = useState(false)
  const [error, setError] = useState('')
  const [success, setSuccess] = useState(null)
  const [showConfirm, setShowConfirm] = useState(false)

  const [basic, setBasic] = useState({
    name: '',
    legal_name: '',
    registration_number: '',
    tin: '',
    vat_number: '',
    industry: 'Tourism',
    business_type: 'Ltd',
    incorporation_date: '',
  })

  const [address, setAddress] = useState({
    country: '',
    region: '',
    city: '',
    address_line_1: '',
    address_line_2: '',
    postal_code: '',
    google_map_location: '',
  })

  const [contact, setContact] = useState({
    phone: '',
    alt_phone: '',
    email: '',
    website: '',
    whatsapp: '',
  })

  const [branding, setBranding] = useState({
    logo: null,
    email_logo: null,
    document_logo: null,
    logo_preview: null,
    email_logo_preview: null,
    document_logo_preview: null,
  })

  const [finance, setFinance] = useState({
    default_currency: 'USD',
    multi_currency_enabled: false,
    financial_year_start: 1,
    tax_enabled: false,
  })

  const [userSetup, setUserSetup] = useState({
    admin_name: '',
    admin_email: '',
    admin_phone: '',
    admin_password: '',
    admin_role: 'Company Admin',
  })

  const [notifications, setNotifications] = useState({
    notify_email: true,
    notify_whatsapp: false,
    notify_sms: false,
    notify_on: ['new_lead', 'quote_sent', 'booking_confirmed', 'payment_received'],
  })

  function setB(field) {
    return (event) => setBasic((prev) => ({ ...prev, [field]: event.target.value }))
  }

  function setA(field) {
    return (event) => setAddress((prev) => ({ ...prev, [field]: event.target.value }))
  }

  function setC(field) {
    return (event) => setContact((prev) => ({ ...prev, [field]: event.target.value }))
  }

  function setF(field) {
    return (event) => setFinance((prev) => ({ ...prev, [field]: event.target.value }))
  }

  function setFBool(field) {
    return (event) => setFinance((prev) => ({ ...prev, [field]: event.target.checked }))
  }

  function setU(field) {
    return (event) => setUserSetup((prev) => ({ ...prev, [field]: event.target.value }))
  }

  function setNBool(field) {
    return (event) => setNotifications((prev) => ({ ...prev, [field]: event.target.checked }))
  }

  function handleFileChange(fileField, previewField, event) {
    const file = event.target.files[0]

    if (!file) {
      return
    }

    const reader = new FileReader()
    reader.onload = (readerEvent) => {
      setBranding((prev) => ({
        ...prev,
        [fileField]: file,
        [previewField]: readerEvent.target.result,
      }))
    }

    reader.readAsDataURL(file)
  }

  function toggleNotifyOn(key) {
    setNotifications((prev) => {
      const exists = prev.notify_on.includes(key)

      return {
        ...prev,
        notify_on: exists
          ? prev.notify_on.filter((k) => k !== key)
          : [...prev.notify_on, key],
      }
    })
  }

  function canAdvance() {
    if (step === 1) {
      return basic.name.trim().length > 0
    }

    if (step === 6) {
      return (
        userSetup.admin_name.trim().length > 0 &&
        userSetup.admin_email.trim().length > 0 &&
        userSetup.admin_password.length >= 8
      )
    }

    return true
  }

  function renderConfirm() {
    const sections = [
      {
        title: 'Basic Info',
        icon: '🏢',
        rows: [
          { label: 'Company Name', value: basic.name },
          { label: 'Legal Name', value: basic.legal_name || '—' },
          { label: 'Industry', value: `${basic.industry} · ${basic.business_type}` },
          { label: 'Registration No.', value: basic.registration_number || '—' },
          { label: 'TIN', value: basic.tin || '—' },
          { label: 'Incorporation Date', value: basic.incorporation_date || '—' },
        ],
      },
      {
        title: 'Address',
        icon: '📍',
        rows: [
          { label: 'Country', value: address.country || '—' },
          { label: 'City / Region', value: [address.city, address.region].filter(Boolean).join(', ') || '—' },
          { label: 'Street', value: [address.address_line_1, address.address_line_2].filter(Boolean).join(', ') || '—' },
          { label: 'Postal Code', value: address.postal_code || '—' },
        ],
      },
      {
        title: 'Contact',
        icon: '📞',
        rows: [
          { label: 'Phone', value: contact.phone || '—' },
          { label: 'Email', value: contact.email || '—' },
          { label: 'Website', value: contact.website || '—' },
          { label: 'WhatsApp', value: contact.whatsapp || '—' },
        ],
      },
      {
        title: 'Finance Settings',
        icon: '💰',
        rows: [
          { label: 'Default Currency', value: finance.default_currency },
          { label: 'Multi-Currency', value: finance.multi_currency_enabled ? 'Enabled' : 'Disabled' },
          { label: 'Financial Year Start', value: MONTHS[finance.financial_year_start - 1] },
          { label: 'Tax Module', value: finance.tax_enabled ? 'Enabled' : 'Disabled' },
        ],
      },
      {
        title: 'Admin User',
        icon: '👤',
        rows: [
          { label: 'Full Name', value: userSetup.admin_name },
          { label: 'Email', value: userSetup.admin_email },
          { label: 'Phone', value: userSetup.admin_phone || '—' },
          { label: 'Role', value: userSetup.admin_role },
          { label: 'Password', value: '••••••••' },
        ],
      },
      {
        title: 'Notifications',
        icon: '🔔',
        rows: [
          {
            label: 'Channels',
            value: [
              notifications.notify_email && 'Email',
              notifications.notify_whatsapp && 'WhatsApp',
              notifications.notify_sms && 'SMS',
            ].filter(Boolean).join(' · ') || 'None',
          },
          {
            label: 'Events',
            value: notifications.notify_on
              .map((k) => NOTIFY_EVENTS.find((e) => e.key === k)?.label)
              .filter(Boolean)
              .join(', ') || 'None',
          },
        ],
      },
    ]

    return (
      <div className="wiz-confirm-overlay">
        <div className="wiz-confirm-modal">

          {/* Header */}
          <div className="wiz-confirm-header">
            <div className="wiz-confirm-logo-wrap">
              {branding.logo_preview
                ? <img src={branding.logo_preview} alt="logo" className="wiz-confirm-logo-img" />
                : <div className="wiz-confirm-logo-placeholder">{basic.name.charAt(0).toUpperCase() || '?'}</div>}
            </div>
            <div className="wiz-confirm-identity">
              <h2>{basic.name}</h2>
              <p>{basic.industry} · {basic.business_type}{[address.city, address.country].filter(Boolean).length ? ` · ${[address.city, address.country].filter(Boolean).join(', ')}` : ''}</p>
            </div>
            <button type="button" className="wiz-close" onClick={() => setShowConfirm(false)} aria-label="Close">✕</button>
          </div>

          {/* Body */}
          <div className="wiz-confirm-body">
            <p className="wiz-confirm-intro">Please review all details carefully before creating this company. Once created, a welcome email will be sent to the admin user.</p>

            {sections.map((section) => (
              <div key={section.title} className="wiz-confirm-section">
                <div className="wiz-confirm-section-title">
                  <span className="wiz-confirm-section-icon">{section.icon}</span>
                  {section.title}
                </div>
                <div className="wiz-confirm-grid">
                  {section.rows.map((row) => (
                    <div key={row.label} className="wiz-confirm-row">
                      <span className="wiz-confirm-label">{row.label}</span>
                      <strong className="wiz-confirm-value">{row.value}</strong>
                    </div>
                  ))}
                </div>
              </div>
            ))}

            {error ? <p className="wiz-error" style={{ marginTop: '12px' }}>{error}</p> : null}
          </div>

          {/* Footer */}
          <div className="wiz-confirm-footer">
            <button type="button" className="wiz-btn-ghost" onClick={() => setShowConfirm(false)} disabled={isSaving}>
              ← Edit Details
            </button>
            <button type="button" className="wiz-btn-primary wiz-confirm-submit" disabled={isSaving} onClick={handleSubmit}>
              {isSaving ? 'Creating company…' : '✓ Confirm & Create Company'}
            </button>
          </div>

        </div>
      </div>
    )
  }

  async function handleSubmit() {
    setIsSaving(true)
    setError('')

    try {
      const form = new FormData()

      // Basic
      const basicEntries = {
        name: basic.name, legal_name: basic.legal_name,
        registration_number: basic.registration_number, tin: basic.tin,
        vat_number: basic.vat_number, industry: basic.industry,
        business_type: basic.business_type, incorporation_date: basic.incorporation_date,
      }

      for (const [k, v] of Object.entries(basicEntries)) {
        if (v) form.append(k, v)
      }

      // Address
      const addressEntries = {
        country: address.country, region: address.region, city: address.city,
        address_line_1: address.address_line_1, address_line_2: address.address_line_2,
        postal_code: address.postal_code, google_map_location: address.google_map_location,
      }

      for (const [k, v] of Object.entries(addressEntries)) {
        if (v) form.append(k, v)
      }

      // Contact
      const contactEntries = {
        phone: contact.phone, alt_phone: contact.alt_phone, email: contact.email,
        website: contact.website, whatsapp: contact.whatsapp,
      }

      for (const [k, v] of Object.entries(contactEntries)) {
        if (v) form.append(k, v)
      }

      // Branding files
      if (branding.logo) form.append('logo', branding.logo)
      if (branding.email_logo) form.append('email_logo', branding.email_logo)
      if (branding.document_logo) form.append('document_logo', branding.document_logo)

      // Finance (always send)
      form.append('default_currency', finance.default_currency)
      form.append('multi_currency_enabled', finance.multi_currency_enabled ? '1' : '0')
      form.append('financial_year_start', String(finance.financial_year_start))
      form.append('tax_enabled', finance.tax_enabled ? '1' : '0')

      // Admin user
      form.append('admin_name', userSetup.admin_name)
      form.append('admin_email', userSetup.admin_email)
      if (userSetup.admin_phone) form.append('admin_phone', userSetup.admin_phone)
      form.append('admin_password', userSetup.admin_password)
      form.append('admin_role', userSetup.admin_role)

      // Notifications
      form.append('notify_email', notifications.notify_email ? '1' : '0')
      form.append('notify_whatsapp', notifications.notify_whatsapp ? '1' : '0')
      form.append('notify_sms', notifications.notify_sms ? '1' : '0')
      notifications.notify_on.forEach((ev) => form.append('notify_on[]', ev))

      const response = await fetch(`${apiBaseUrl}/admin/companies`, {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          Authorization: `Bearer ${apiToken}`,
        },
        body: form,
      })

      const result = await response.json()

      if (!response.ok) {
        const messages = result.errors
          ? Object.values(result.errors).flat().join(' ')
          : (result.message || 'Could not create company.')

        throw new Error(messages)
      }

      setSuccess(result.company)
      onCreated?.(result.company)
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Could not create company.')
    } finally {
      setIsSaving(false)
    }
  }

  if (success) {
    return (
      <div className="wiz-overlay">
        <div className="wiz-modal wiz-success">
          <div className="wiz-success-icon">✓</div>
          <h2>Company Created!</h2>
          <p className="wiz-success-code">{success.company_code}</p>
          <p>{success.name} has been set up and is ready.</p>
          <button type="button" className="wiz-btn-primary" onClick={onClose}>Done</button>
        </div>
      </div>
    )
  }

  return (
    <div className="wiz-overlay">
      <div className="wiz-modal">
        <div className="wiz-header">
          <div>
            <h2>Create New Company</h2>
            <p>Step {step} of {STEPS.length} — {STEPS[step - 1].label}</p>
          </div>
          <button type="button" className="wiz-close" onClick={onClose} aria-label="Close">✕</button>
        </div>

        {/* Step progress */}
        <div className="wiz-stepper">
          {STEPS.map((s) => (
            <button
              key={s.id}
              type="button"
              className={`wiz-step-item ${s.id === step ? 'active' : ''} ${s.id < step ? 'done' : ''}`}
              onClick={() => s.id < step && setStep(s.id)}
              disabled={s.id > step}
            >
              <div className="wiz-step-bubble">{s.id < step ? '✓' : s.id}</div>
              <span>{s.label}</span>
            </button>
          ))}
          <div className="wiz-step-track">
            <div className="wiz-step-fill" style={{ width: `${((step - 1) / (STEPS.length - 1)) * 100}%` }} />
          </div>
        </div>

        {/* Body */}
        <div className="wiz-body">
          {error ? <div className="wiz-error">{error}</div> : null}

          {/* Step 1 – Basic Info */}
          {step === 1 && (
            <div className="wiz-section">
              <h3>🏢 Basic Information</h3>
              <div className="wiz-grid-2">
                <Field label="Company Name" required>
                  <input value={basic.name} onChange={setB('name')} placeholder="e.g. Atlas Safari Group" />
                </Field>
                <Field label="Legal / Registered Name">
                  <input value={basic.legal_name} onChange={setB('legal_name')} placeholder="As on registration document" />
                </Field>
                <Field label="Registration Number">
                  <input value={basic.registration_number} onChange={setB('registration_number')} />
                </Field>
                <Field label="TIN (Tax Identification Number)">
                  <input value={basic.tin} onChange={setB('tin')} />
                </Field>
                <Field label="VAT Number">
                  <input value={basic.vat_number} onChange={setB('vat_number')} />
                </Field>
                <Field label="Industry">
                  <select value={basic.industry} onChange={setB('industry')}>
                    {INDUSTRIES.map((i) => <option key={i}>{i}</option>)}
                  </select>
                </Field>
                <Field label="Business Type">
                  <select value={basic.business_type} onChange={setB('business_type')}>
                    {BUSINESS_TYPES.map((b) => <option key={b}>{b}</option>)}
                  </select>
                </Field>
                <Field label="Incorporation Date">
                  <input type="date" value={basic.incorporation_date} onChange={setB('incorporation_date')} />
                </Field>
              </div>
              <p className="wiz-hint">Company Code (e.g. ZT-001) will be auto-generated on save.</p>
            </div>
          )}

          {/* Step 2 – Address */}
          {step === 2 && (
            <div className="wiz-section">
              <h3>📍 Company Address</h3>
              <div className="wiz-grid-2">
                <Field label="Country">
                  <input value={address.country} onChange={setA('country')} placeholder="Tanzania" />
                </Field>
                <Field label="Region / State">
                  <input value={address.region} onChange={setA('region')} placeholder="Arusha" />
                </Field>
                <Field label="City">
                  <input value={address.city} onChange={setA('city')} />
                </Field>
                <Field label="Postal Code">
                  <input value={address.postal_code} onChange={setA('postal_code')} />
                </Field>
              </div>
              <Field label="Address Line 1" full>
                <input value={address.address_line_1} onChange={setA('address_line_1')} placeholder="Street / Building" />
              </Field>
              <Field label="Address Line 2" full>
                <input value={address.address_line_2} onChange={setA('address_line_2')} placeholder="Floor / Suite (optional)" />
              </Field>
              <Field label="Google Map Location (optional)" full>
                <input value={address.google_map_location} onChange={setA('google_map_location')} placeholder="Paste Google Maps link" />
              </Field>
            </div>
          )}

          {/* Step 3 – Contact */}
          {step === 3 && (
            <div className="wiz-section">
              <h3>📞 Contact Details</h3>
              <div className="wiz-grid-2">
                <Field label="Phone Number">
                  <input type="tel" value={contact.phone} onChange={setC('phone')} placeholder="+255 700 000 000" />
                </Field>
                <Field label="Alternative Phone">
                  <input type="tel" value={contact.alt_phone} onChange={setC('alt_phone')} />
                </Field>
                <Field label="Email Address">
                  <input type="email" value={contact.email} onChange={setC('email')} placeholder="info@company.com" />
                </Field>
                <Field label="WhatsApp Number">
                  <input type="tel" value={contact.whatsapp} onChange={setC('whatsapp')} placeholder="+255 700 000 000" />
                </Field>
              </div>
              <Field label="Website" full>
                <input type="url" value={contact.website} onChange={setC('website')} placeholder="https://www.company.com" />
              </Field>
            </div>
          )}

          {/* Step 4 – Branding */}
          {step === 4 && (
            <div className="wiz-section">
              <h3>🌿 Branding</h3>
              <p className="wiz-hint">Upload PNG or JPG. Max 2 MB per image. All fields optional.</p>
              <div className="wiz-brand-row">
                {[
                  { file: 'logo', preview: 'logo_preview', label: 'Company Logo', hint: 'Main brand mark' },
                  { file: 'email_logo', preview: 'email_logo_preview', label: 'Email Header Logo', hint: 'Top of outgoing emails' },
                  { file: 'document_logo', preview: 'document_logo_preview', label: 'Document Logo', hint: 'Invoices & quotes' },
                ].map((item) => (
                  <div key={item.file} className="wiz-brand-card">
                    <div className="wiz-brand-preview">
                      {branding[item.preview]
                        ? <img src={branding[item.preview]} alt={item.label} />
                        : <span>No image</span>}
                    </div>
                    <strong>{item.label}</strong>
                    <p>{item.hint}</p>
                    <label className="wiz-upload-btn">
                      Choose file
                      <input
                        type="file"
                        accept="image/*"
                        onChange={(e) => handleFileChange(item.file, item.preview, e)}
                        style={{ display: 'none' }}
                      />
                    </label>
                  </div>
                ))}
              </div>
            </div>
          )}

          {/* Step 5 – Finance */}
          {step === 5 && (
            <div className="wiz-section">
              <h3>💰 Finance Settings</h3>
              <div className="wiz-grid-2">
                <Field label="Default Currency">
                  <select value={finance.default_currency} onChange={setF('default_currency')}>
                    {CURRENCIES.map((c) => <option key={c}>{c}</option>)}
                  </select>
                </Field>
                <Field label="Financial Year Start Month">
                  <select
                    value={finance.financial_year_start}
                    onChange={(e) => setFinance((prev) => ({ ...prev, financial_year_start: Number(e.target.value) }))}
                  >
                    {MONTHS.map((m, i) => <option key={m} value={i + 1}>{m}</option>)}
                  </select>
                </Field>
              </div>
              <div className="wiz-toggles">
                <label className="wiz-toggle-row">
                  <input type="checkbox" checked={finance.multi_currency_enabled} onChange={setFBool('multi_currency_enabled')} />
                  <span>
                    <strong>Multi-currency Enabled</strong>
                    <small>Allow transactions in multiple currencies</small>
                  </span>
                </label>
                <label className="wiz-toggle-row">
                  <input type="checkbox" checked={finance.tax_enabled} onChange={setFBool('tax_enabled')} />
                  <span>
                    <strong>Tax Enabled</strong>
                    <small>Enable tax calculations on invoices and quotes</small>
                  </span>
                </label>
              </div>
            </div>
          )}

          {/* Step 6 – User Setup */}
          {step === 6 && (
            <div className="wiz-section">
              <h3>👥 First Admin User</h3>
              <p className="wiz-hint">This user will have access to log in to the company portal.</p>
              <div className="wiz-grid-2">
                <Field label="Full Name" required>
                  <input value={userSetup.admin_name} onChange={setU('admin_name')} placeholder="Jane Doe" />
                </Field>
                <Field label="Role">
                  <select value={userSetup.admin_role} onChange={setU('admin_role')}>
                    {TENANT_ROLES.map((r) => <option key={r}>{r}</option>)}
                  </select>
                </Field>
                <Field label="Email Address" required>
                  <input type="email" value={userSetup.admin_email} onChange={setU('admin_email')} placeholder="admin@company.com" />
                </Field>
                <Field label="Phone">
                  <input type="tel" value={userSetup.admin_phone} onChange={setU('admin_phone')} />
                </Field>
                <Field label="Password (min 8 characters)" required full>
                  <input type="password" value={userSetup.admin_password} onChange={setU('admin_password')} placeholder="••••••••" />
                </Field>
              </div>
              {userSetup.admin_password.length > 0 && userSetup.admin_password.length < 8 && (
                <p className="wiz-field-error">Password must be at least 8 characters.</p>
              )}
            </div>
          )}

          {/* Step 7 – Notifications */}
          {step === 7 && (
            <div className="wiz-section">
              <h3>🔔 Notifications</h3>
              <div className="wiz-toggles">
                <label className="wiz-toggle-row">
                  <input type="checkbox" checked={notifications.notify_email} onChange={setNBool('notify_email')} />
                  <span>
                    <strong>Email Notifications</strong>
                    <small>Send system emails for key events</small>
                  </span>
                </label>
                <label className="wiz-toggle-row">
                  <input type="checkbox" checked={notifications.notify_whatsapp} onChange={setNBool('notify_whatsapp')} />
                  <span>
                    <strong>WhatsApp Notifications</strong>
                    <small>Send WhatsApp messages via integration</small>
                  </span>
                </label>
                <label className="wiz-toggle-row">
                  <input type="checkbox" checked={notifications.notify_sms} onChange={setNBool('notify_sms')} />
                  <span>
                    <strong>SMS Notifications</strong>
                    <small>Send SMS alerts via connected SMS gateway</small>
                  </span>
                </label>
              </div>

              <div className="wiz-events-section">
                <p className="wiz-section-title">Notify on these events:</p>
                <div className="wiz-events-grid">
                  {NOTIFY_EVENTS.map((ev) => (
                    <label key={ev.key} className="wiz-event-toggle">
                      <input
                        type="checkbox"
                        checked={notifications.notify_on.includes(ev.key)}
                        onChange={() => toggleNotifyOn(ev.key)}
                      />
                      <span>{ev.label}</span>
                    </label>
                  ))}
                </div>
              </div>

              {/* Summary card */}
              <div className="wiz-summary">
                <h4>Review Summary</h4>
                <div className="wiz-summary-grid">
                  <div className="wiz-summary-item">
                    <span>Company</span>
                    <strong>{basic.name || '—'}</strong>
                  </div>
                  <div className="wiz-summary-item">
                    <span>Industry</span>
                    <strong>{basic.industry} · {basic.business_type}</strong>
                  </div>
                  <div className="wiz-summary-item">
                    <span>Location</span>
                    <strong>{[address.city, address.country].filter(Boolean).join(', ') || '—'}</strong>
                  </div>
                  <div className="wiz-summary-item">
                    <span>Currency</span>
                    <strong>{finance.default_currency}</strong>
                  </div>
                  <div className="wiz-summary-item">
                    <span>Admin User</span>
                    <strong>{userSetup.admin_name || '—'}</strong>
                  </div>
                  <div className="wiz-summary-item">
                    <span>Admin Email</span>
                    <strong>{userSetup.admin_email || '—'}</strong>
                  </div>
                </div>
              </div>
            </div>
          )}
        </div>

        {showConfirm ? renderConfirm() : null}

        {/* Footer */}
        <div className="wiz-footer">
          <button
            type="button"
            className="wiz-btn-ghost"
            onClick={step === 1 ? onClose : () => setStep((s) => s - 1)}
          >
            {step === 1 ? 'Cancel' : '← Back'}
          </button>

          <div className="wiz-step-counter">{step} / {STEPS.length}</div>

          {step < 7
            ? (
              <button
                type="button"
                className="wiz-btn-primary"
                disabled={!canAdvance()}
                onClick={() => setStep((s) => s + 1)}
              >
                Next →
              </button>
            )
            : (
              <button
                type="button"
                className="wiz-btn-primary"
                onClick={() => { setError(''); setShowConfirm(true) }}
              >
                Review & Create →
              </button>
            )}
        </div>
      </div>
    </div>
  )
}

export default CompanyWizard
