{{ range .Headers }}
  {{ . }}\t
{{ end }}  
\n
{{ range .Rows }}
  {{ range . }}
    {{ . }}\t
  {{ end }}
  \n
{{ end }}
