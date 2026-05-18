import { useState, useEffect } from 'react'
import './CompanyEditModal.css'

const MONTHS = [
  'January', 'February', 'March', 'April', 'May', 'June',
  'July', 'August', 'September', 'October', 'November', 'December',
]
const CURRENCIES = ['USD', 'TZS', 'EUR', 'GBP', 'KES', 'UGX', 'RWF', 'ZAR']
const INDUSTRIES = ['Tourism', 'Travel', 'Hospitality', 'Safari', 'Adventure', 'Airline', 'Other']
const BUSINESS_TYPES = ['Ltd', 'Sole Proprietor', 'Partnership', 'NGO', 'Association', 'Public Company']
const NOTIFY_EVENTS = [
  { key: 'new_lead', label: 'New Lead' },
  { key: 'quote_sent', label: 'Quote Sent' },
  { key: 'booking_confirmed', label: 'Booking Confirmed' },
  { key: 'payment_received', label: 'Payment Received' },
]

function Field({ label, required, children, full }) {
  return (
    <label className={`cem-field ${full ? 'cem-field-full' : ''}`}>
      <span className="cem-field-label">{label}{required ? <em>*</em> : null}</span>
      {children}
    </label>
  )
}

function Section({ icon, title, children }) {
  return (
    <section className="cem-section">
      <div className="cem-section-title">
        <span className="cem-section-icon">{icon}</span>
        {title}
      </div>
      <div className="cem-section-body">
        {children}
      </div>
    </section>
  )
}

export default function CompanyEditModal({ company, apiBaseUrl, apiToken, onClose, onUpdated }) {
  const [isSaving, setIsSaving] = useState(false)
  const [error, setError] = useState('')
  const [success, setSuccess] = useState('')

  // Basic info
  const [basic, setBasic] = useState({
    name: '',
    legal_name: '',
    registration_number: '',
    tin: '',
    vat_number: '',
    industry: 'Tourism',
    business_type: 'Ltd',
    incorporation_date: '',
    status: 'active',
    subscription_status: 'trial',
  })

  // Address
  const [address, setAddress] = useState({
    country: '',
    region: '',
    city: '',
    address_line_1: '',
    address_line_2: '',
    postal_code: '',
    google_map_location: '',
  })

  // Contact
  const [contact, setContact] = useState({
    phone: '',
    alt_phone: '',
    email: '',
    website: '',
    whatsapp: '',
  })

  // Branding
  const [branding, setBranding] = useState({
    logo: null,
    email_logo: null,
    document_logo: null,
    logo_preview: null,
    email_logo_preview: null,
    document_logo_preview: null,
  })

  // Finance
  const [finance, setFinance] = useState({
    default_currency: 'USD',
    multi_currency_enabled: false,
    financial_year_start: 1,
    tax_enabled: false,
  })

  // Notifications
  const [notify, setNotify] = useState({
    notify_email: true,
    notify_whatsapp: false,
    notify_sms: false,
    notify_on: [],
  })

  // Normalise date string to YYYY-MM-DD (handles full ISO datetime from Laravel)
  function toDateInputValue(val) {
    if (!val) return ''
    // Take first 10 chars covers both "2023-01-15" and "2023-01-15T00:00:00.000000Z"
    return String(val).slice(0, 10)
  }

  // Populate from existing company data
  useEffect(() => {
    if (!company) return
    setBasic({
      name: company.name || '',
      legal_name: company.legal_name || '',
      registration_number: company.registration_number || '',
      tin: company.tin || '',
      vat_number: company.vat_number || '',
      industry: company.industry || 'Tourism',
      business_type: company.business_type || 'Ltd',
      incorporation_date: toDateInputValue(company.incorporation_date),
      status: company.status || 'active',
      subscription_status: company.subscription_status || 'trial',
    })
    setAddress({
      country: company.country || '',
      region: company.region || '',
      city: company.city || '',
      address_line_1: company.address_line_1 || '',
      address_line_2: company.address_line_2 || '',
      postal_code: company.postal_code || '',
      google_map_location: company.google_map_location || '',
    })
    setContact({
      phone: company.phone || '',
      alt_phone: company.alt_phone || '',
      email: company.email || '',
      website: company.website || '',
      whatsapp: company.whatsapp || '',
    })
    setFinance({
      default_currency: company.default_currency || 'USD',
      multi_currency_enabled: Boolean(company.multi_currency_enabled),
      financial_year_start: Number(company.financial_year_start) || 1,
      tax_enabled: Boolean(company.tax_enabled),
    })
    setNotify({
      notify_email: company.notify_email === true || company.notify_email === 1 || company.notify_email === '1',
      notify_whatsapp: company.notify_whatsapp === true || company.notify_whatsapp === 1 || company.notify_whatsapp === '1',
      notify_sms: company.notify_sms === true || company.notify_sms === 1 || company.notify_sms === '1',
      notify_on: Array.isArray(company.notify_on) ? company.notify_on : [],
    })
    // Fully reset branding (prevents stale logos from previous modal open)
    setBranding({
      logo: null,
      email_logo: null,
      document_logo: null,
      logo_preview: company.logo_path
        ? `${apiBaseUrl.replace('/api', '')}/storage/${company.logo_path}`
        : null,
      email_logo_preview: company.email_logo_path
        ? `${apiBaseUrl.replace('/api', '')}/storage/${company.email_logo_path}`
        : null,
      document_logo_preview: company.document_logo_path
        ? `${apiBaseUrl.replace('/api', '')}/storage/${company.document_logo_path}`
        : null,
    })
  }, [company])

  function toggleNotifyEvent(key) {
    setNotify((p) => ({
      ...p,
      notify_on: p.notify_on.includes(key)
        ? p.notify_on.filter((k) => k !== key)
        : [...p.notify_on, key],
    }))
  }

  function handleFileChange(field, e) {
    const file = e.target.files?.[0]
    if (!file) return
    const preview = URL.createObjectURL(file)
    setBranding((p) => ({ ...p, [field]: file, [`${field}_preview`]: preview }))
  }

  async function handleSubmit(e) {
    e.preventDefault()
    if (!basic.name.trim()) {
      setError('Company name is required.')
      return
    }
    setIsSaving(true)
    setError('')
    setSuccess('')

    const fd = new FormData()
    // Basic
    Object.entries(basic).forEach(([k, v]) => fd.append(k, v ?? ''))
    // Address
    Object.entries(address).forEach(([k, v]) => fd.append(k, v ?? ''))
    // Contact
    Object.entries(contact).forEach(([k, v]) => fd.append(k, v ?? ''))
    // Finance
    fd.append('default_currency', finance.default_currency)
    fd.append('multi_currency_enabled', finance.multi_currency_enabled ? '1' : '0')
    fd.append('financial_year_start', String(finance.financial_year_start))
    fd.append('tax_enabled', finance.tax_enabled ? '1' : '0')
    // Notifications
    fd.append('notify_email', notify.notify_email ? '1' : '0')
    fd.append('notify_whatsapp', notify.notify_whatsapp ? '1' : '0')
    fd.append('notify_sms', notify.notify_sms ? '1' : '0')
    notify.notify_on.forEach((ev) => fd.append('notify_on[]', ev))
    // Files (only if changed)
    if (branding.logo) fd.append('logo', branding.logo)
    if (branding.email_logo) fd.append('email_logo', branding.email_logo)
    if (branding.document_logo) fd.append('document_logo', branding.document_logo)
    // Laravel spoofing not needed — we're using POST route directly

    try {
      const res = await fetch(`${apiBaseUrl}/admin/companies/${company.id}`, {
        method: 'POST',
        headers: { Accept: 'application/json', Authorization: `Bearer ${apiToken}` },
        body: fd,
      })
      const result = await res.json()
      if (!res.ok) throw new Error(result?.message || 'Update failed.')
      setSuccess('Company updated successfully.')
      setTimeout(() => {
        onUpdated(result.company || { ...company, ...basic })
      }, 800)
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Update failed.')
    } finally {
      setIsSaving(false)
    }
  }

  const logoInitial = (basic.name || company?.name || 'C').charAt(0).toUpperCase()

  return (
    <div className="cem-overlay" role="dialog" aria-modal="true" aria-label="Edit Company">
      <div className="cem-modal">
        {/* Header */}
        <div className="cem-header">
          <div className="cem-header-logo">
            {branding.logo_preview
              ? <img src={branding.logo_preview} alt="logo" className="cem-logo-img" />
              : <div className="cem-logo-placeholder">{logoInitial}</div>
            }
          </div>
          <div className="cem-header-info">
            <h2>{basic.name || 'Edit Company'}</h2>
            <p>{company?.company_code} · {basic.industry}</p>
          </div>
          <button type="button" className="cem-close" onClick={onClose} aria-label="Close">✕</button>
        </div>

        {/* Body */}
        <form className="cem-body" onSubmit={handleSubmit} id="cem-form">
          {/* Basic Info */}
          <Section icon="🏢" title="Basic Information">
            <div className="cem-grid">
              <Field label="Company Name" required full>
                <input value={basic.name} onChange={(e) => setBasic((p) => ({ ...p, name: e.target.value }))} placeholder="e.g. Savanna Quest Ltd" />
              </Field>
              <Field label="Legal Name">
                <input value={basic.legal_name} onChange={(e) => setBasic((p) => ({ ...p, legal_name: e.target.value }))} />
              </Field>
              <Field label="Registration No.">
                <input value={basic.registration_number} onChange={(e) => setBasic((p) => ({ ...p, registration_number: e.target.value }))} />
              </Field>
              <Field label="TIN">
                <input value={basic.tin} onChange={(e) => setBasic((p) => ({ ...p, tin: e.target.value }))} />
              </Field>
              <Field label="VAT Number">
                <input value={basic.vat_number} onChange={(e) => setBasic((p) => ({ ...p, vat_number: e.target.value }))} />
              </Field>
              <Field label="Industry">
                <select value={basic.industry} onChange={(e) => setBasic((p) => ({ ...p, industry: e.target.value }))}>
                  {INDUSTRIES.map((i) => <option key={i} value={i}>{i}</option>)}
                </select>
              </Field>
              <Field label="Business Type">
                <select value={basic.business_type} onChange={(e) => setBasic((p) => ({ ...p, business_type: e.target.value }))}>
                  {BUSINESS_TYPES.map((b) => <option key={b} value={b}>{b}</option>)}
                </select>
              </Field>
              <Field label="Incorporation Date">
                <input type="date" value={basic.incorporation_date} onChange={(e) => setBasic((p) => ({ ...p, incorporation_date: e.target.value }))} />
              </Field>
              <Field label="Status">
                <select value={basic.status} onChange={(e) => setBasic((p) => ({ ...p, status: e.target.value }))}>
                  <option value="active">Active</option>
                  <option value="inactive">Inactive</option>
                  <option value="suspended">Suspended</option>
                </select>
              </Field>
              <Field label="Subscription Status">
                <select value={basic.subscription_status} onChange={(e) => setBasic((p) => ({ ...p, subscription_status: e.target.value }))}>
                  <option value="trial">Trial</option>
                  <option value="active">Active</option>
                  <option value="past_due">Past Due</option>
                  <option value="cancelled">Cancelled</option>
                </select>
              </Field>
            </div>
          </Section>

          {/* Address */}
          <Section icon="📍" title="Address">
            <div className="cem-grid">
              <Field label="Country">
                <input value={address.country} onChange={(e) => setAddress((p) => ({ ...p, country: e.target.value }))} />
              </Field>
              <Field label="Region / State">
                <input value={address.region} onChange={(e) => setAddress((p) => ({ ...p, region: e.target.value }))} />
              </Field>
              <Field label="City">
                <input value={address.city} onChange={(e) => setAddress((p) => ({ ...p, city: e.target.value }))} />
              </Field>
              <Field label="Postal Code">
                <input value={address.postal_code} onChange={(e) => setAddress((p) => ({ ...p, postal_code: e.target.value }))} />
              </Field>
              <Field label="Address Line 1" full>
                <input value={address.address_line_1} onChange={(e) => setAddress((p) => ({ ...p, address_line_1: e.target.value }))} />
              </Field>
              <Field label="Address Line 2" full>
                <input value={address.address_line_2} onChange={(e) => setAddress((p) => ({ ...p, address_line_2: e.target.value }))} />
              </Field>
              <Field label="Google Maps Link" full>
                <input value={address.google_map_location} onChange={(e) => setAddress((p) => ({ ...p, google_map_location: e.target.value }))} />
              </Field>
            </div>
          </Section>

          {/* Contact */}
          <Section icon="📞" title="Contact Details">
            <div className="cem-grid">
              <Field label="Phone">
                <input value={contact.phone} onChange={(e) => setContact((p) => ({ ...p, phone: e.target.value }))} />
              </Field>
              <Field label="Alt Phone">
                <input value={contact.alt_phone} onChange={(e) => setContact((p) => ({ ...p, alt_phone: e.target.value }))} />
              </Field>
              <Field label="Email">
                <input type="email" value={contact.email} onChange={(e) => setContact((p) => ({ ...p, email: e.target.value }))} />
              </Field>
              <Field label="Website">
                <input type="url" value={contact.website} onChange={(e) => setContact((p) => ({ ...p, website: e.target.value }))} />
              </Field>
              <Field label="WhatsApp">
                <input value={contact.whatsapp} onChange={(e) => setContact((p) => ({ ...p, whatsapp: e.target.value }))} />
              </Field>
            </div>
          </Section>

          {/* Branding */}
          <Section icon="🌿" title="Branding / Logos">
            <div className="cem-branding-grid">
              {[
                { field: 'logo', label: 'Main Logo', previewKey: 'logo_preview' },
                { field: 'email_logo', label: 'Email Logo', previewKey: 'email_logo_preview' },
                { field: 'document_logo', label: 'Document Logo', previewKey: 'document_logo_preview' },
              ].map(({ field, label, previewKey }) => (
                <div key={field} className="cem-logo-slot">
                  <span className="cem-field-label">{label}</span>
                  <div className="cem-logo-preview-box">
                    {branding[previewKey]
                      ? <img src={branding[previewKey]} alt={label} className="cem-logo-preview-img" />
                      : <span className="cem-logo-empty">No image</span>
                    }
                  </div>
                  <label className="cem-logo-upload-btn">
                    Replace
                    <input type="file" accept="image/*" style={{ display: 'none' }} onChange={(e) => handleFileChange(field, e)} />
                  </label>
                </div>
              ))}
            </div>
          </Section>

          {/* Finance */}
          <Section icon="💰" title="Finance Settings">
            <div className="cem-grid">
              <Field label="Default Currency">
                <select value={finance.default_currency} onChange={(e) => setFinance((p) => ({ ...p, default_currency: e.target.value }))}>
                  {CURRENCIES.map((c) => <option key={c} value={c}>{c}</option>)}
                </select>
              </Field>
              <Field label="Financial Year Start">
                <select value={finance.financial_year_start} onChange={(e) => setFinance((p) => ({ ...p, financial_year_start: Number(e.target.value) }))}>
                  {MONTHS.map((m, i) => <option key={m} value={i + 1}>{m}</option>)}
                </select>
              </Field>
              <Field label="Multi-Currency" full>
                <label className="cem-toggle">
                  <input
                    type="checkbox"
                    checked={finance.multi_currency_enabled}
                    onChange={(e) => setFinance((p) => ({ ...p, multi_currency_enabled: e.target.checked }))}
                  />
                  <span className="cem-toggle-slider" />
                  Enable multi-currency support
                </label>
              </Field>
              <Field label="Tax" full>
                <label className="cem-toggle">
                  <input
                    type="checkbox"
                    checked={finance.tax_enabled}
                    onChange={(e) => setFinance((p) => ({ ...p, tax_enabled: e.target.checked }))}
                  />
                  <span className="cem-toggle-slider" />
                  Enable tax calculations
                </label>
              </Field>
            </div>
          </Section>

          {/* Notifications */}
          <Section icon="🔔" title="Notifications">
            <div className="cem-grid">
              <Field label="Channels" full>
                <div className="cem-checkgroup">
                  {[
                    { key: 'notify_email', label: '📧 Email' },
                    { key: 'notify_whatsapp', label: '💬 WhatsApp' },
                    { key: 'notify_sms', label: '📱 SMS' },
                  ].map(({ key, label }) => (
                    <label key={key} className="cem-check-item">
                      <input
                        type="checkbox"
                        checked={notify[key]}
                        onChange={(e) => setNotify((p) => ({ ...p, [key]: e.target.checked }))}
                      />
                      {label}
                    </label>
                  ))}
                </div>
              </Field>
              <Field label="Notify On Events" full>
                <div className="cem-checkgroup">
                  {NOTIFY_EVENTS.map(({ key, label }) => (
                    <label key={key} className="cem-check-item">
                      <input
                        type="checkbox"
                        checked={notify.notify_on.includes(key)}
                        onChange={() => toggleNotifyEvent(key)}
                      />
                      {label}
                    </label>
                  ))}
                </div>
              </Field>
            </div>
          </Section>

          {error ? <p className="cem-error">{error}</p> : null}
          {success ? <p className="cem-success">{success}</p> : null}
        </form>

        {/* Footer */}
        <div className="cem-footer">
          <button type="button" className="ghost-btn" onClick={onClose}>Cancel</button>
          <button
            type="submit"
            form="cem-form"
            className="primary-btn"
            disabled={isSaving}
          >
            {isSaving ? 'Saving…' : '✓ Save Changes'}
          </button>
        </div>
      </div>
    </div>
  )
}
